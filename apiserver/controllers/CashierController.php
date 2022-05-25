<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\OrderModel;
use common\models\DepositTradeModel;
use common\models\DepositAccountModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;
use common\library\Plugin;

use apiserver\library\Respond;

/**
 * @Id CashierController.php 2018.11.23 $
 * @author yxyc
 */

class CashierController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 提交收银台交易订单支付请求
	 * @api 接口访问地址: http://api.xxx.com/cashier/pay
	 */
	public function actionPay()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		if (empty($post->orderId)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('orderId_empty'));
		}
		$post->orderId = implode(',', (array)$post->orderId);

		if (!isset($post->payment_code) && empty($post->payment_code)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('pls_select_paymethod'));
		}

		// 如果是余额支付，验证支付密码
		if (in_array(strtolower($post->payment_code), array('deposit'))) {
			if (!DepositAccountModel::checkAccountPassword($post->password, Yii::$app->user->id)) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('password_error'));
			}
		}

		// 获取交易数据
		list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($post->orderId, Yii::$app->user->id);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $errorMsg);
		}

		$payment = Plugin::getInstance('payment')->build();
		list($all_payments, $cod_payments, $errorMsg) = $payment->getAvailablePayments($orderInfo, true, true);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $errorMsg);
		}

		$arr_payments = $payment->getKeysOfPayments($all_payments);
		array_push($arr_payments, 'latipay');

		// return $respond->output(true, null, $all_payments);

		// 检查用户所使用的付款方式是否在允许的范围内
		if (!in_array($post->payment_code, $arr_payments)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('payment_not_available'));
		}

		// 买家选择的支付方式更新到交易表
		if (DepositTradeModel::updateTradePayment($orderInfo, $post->payment_code) === false) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('payment_save_trade_fail'));
		}

		$payment_info = $all_payments[$post->payment_code];
		if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER))) {
			$isCod = strtoupper($post->payment_code) == 'COD';
			OrderModel::updateOrderPayment($orderInfo, $isCod ? $cod_payments : $payment_info, $isCod);
		}

		if( strcmp($post->payment_code, 'latipay') == 0 ){

		}
		else{
			// 生成支付参数
			list($payTradeNo, $payform) = Plugin::getInstance('payment')->build($post->payment_code, $post)->getPayform($orderInfo, false);
			$this->params = array_merge($payform, ['payTradeNo' => $payTradeNo]);
		}
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 微信获取CODE后跳回的地址（适用于微信公众号支付）
	 * @api 接口访问地址: http://api.xxx.com/cashier/wxpay
	 */
	public function actionWxpay()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		if (!$post->payTradeNo || !($orderInfo = DepositTradeModel::getTradeInfoForNotify($post->payTradeNo))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_info_empty'));
        }
		
		if(!in_array($orderInfo['payment_code'], array('wxpay', 'wxh5pay'))){
			return $respond->output(Respond::HANDLE_INVALID, Language::get('Hacking Attempt'));
		}
			
		$payment = Plugin::getInstance('payment')->build($orderInfo['payment_code']);
		if($orderInfo['payment_code'] == 'wxpay') {
			$jsApiParameters = $payment->getParameters($post->code, $orderInfo, $post->payTradeNo);
		}

		$orderInfo = array(
			'title' => $orderInfo['title'], 
			'payee' => Yii::$app->params['site_name'], 
			'bizIdentity' => $orderInfo['bizIdentity'], 
			'amount' => $orderInfo['amount']
		);
		return $respond->output(true, null, ['orderInfo' => $orderInfo, 'jsApiParameters' => $jsApiParameters ? json_decode($jsApiParameters) : '']);
	}

	/**
	 * 获取收银台交易订单数据集合
	 * @api 接口访问地址: http://api.xxx.com/cashier/build
	 */
	public function actionBuild()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (empty($post->bizOrderId)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('bizOrderId_empty'));
		}

		// 订单类型
		if (!in_array($post->bizIdentity, [Def::TRADE_ORDER, Def::TRADE_RECHARGE, Def::TRADE_DRAW, Def::TRADE_BUYAPP])) {
			$post->bizIdentity = Def::TRADE_ORDER;
		}

		// 普通购物订单
		$model = new \frontend\models\CashierTradeOrderForm();
		if (in_array($post->bizIdentity, [Def::TRADE_ORDER])) {
			$orderId = $model->getOrderId($post);
		}

		// 购买营销工具订单
		$model = new \frontend\models\CashierTradeBuyappForm();
		if (in_array($post->bizIdentity, [Def::TRADE_BUYAPP])) {
			$orderId = $model->getOrderId($post);
		}

		if (!$orderId || $model->errors) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// 有可能是多少个交易号
		return $this->paybuild($respond, implode(',', $orderId));
	}

	private function paybuild($respond, $orderId)
	{
		list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($orderId, Yii::$app->user->id);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $errorMsg);
		}

		list($all_payments, $cod_payments, $errorMsg) = Plugin::getInstance('payment')->build()->getAvailablePayments($orderInfo, true, true);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $errorMsg);
		}
		$this->params['payments'] = $this->removeFieldsOfPayment($all_payments);
		$this->params['orderInfo'] = $this->removeFieldsOfTrade($orderInfo);
		$this->params['orderId'] = explode(',', $orderId);

		return $respond->output(true, null, $this->params);
	}

	/**
	 * 移除接口不需要的字段
	 */
	private function removeFieldsOfPayment($payments)
	{
		foreach ($payments as $key => $value) {
			unset($value['id'], $value['instance'], $value['enabled'], $value['config']);
			$payments[$key] = $value;
		}

		return $payments;
	}

	/**
	 * 移除接口不需要的字段
	 */
	private function removeFieldsOfTrade($orderInfo)
	{
		unset($orderInfo['orderList']);
		foreach ($orderInfo['tradeList'] as $key => $value) {
			foreach ($value as $k => $v) {
				if (!in_array($k, ['trade_id', 'tradeNo', 'bizOrderId', 'bizIdentity', 'seller_id', 'amount'])) {
					unset($orderInfo['tradeList'][$key][$k]);
				}
			}
			$orderInfo['tradeList'][$key]['store_name'] = $value['seller'];
			$orderInfo['tradeList'][$key]['goods_name'] = $value['name'];
		}

		return $orderInfo;
	}
}
