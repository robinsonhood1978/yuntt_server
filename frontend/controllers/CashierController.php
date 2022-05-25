<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\models\OrderModel;
use common\models\AppbuylogModel;
use common\models\DepositTradeModel;
use common\models\DepositAccountModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Plugin;
use common\library\Page;
use common\library\Def;
use Da\QrCode\QrCode;

/**
 * @Id CashierController.php 2018.7.17 $
 * @author mosir
 */

class CashierController extends \common\controllers\BaseUserController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}

	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		if (!$post->order_id) {
			return Message::warning(Language::get('no_such_order'));
		}

		// 支付多个订单
		$bizOrderId = implode(',', OrderModel::find()->select('order_sn')->where(['buyer_id' => Yii::$app->user->id])->andWhere(['in', 'order_id', explode(',', $post->order_id)])->column());

		// 到收银台付款
		return $this->redirect(['cashier/gateway', 'bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_ORDER]);
	}

	public function actionGateway()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

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
			return Message::warning($model->errors);
		}

		return $this->redirect(['cashier/pay', 'orderId' => implode(',', $orderId)]);
	}

	// 网关支付
	public function actionPay()
	{
		$orderId = Basewind::trimAll(Yii::$app->request->get('orderId'), true);

		if (!Yii::$app->request->isPost) {
			list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($orderId, Yii::$app->user->id);
			if ($errorMsg !== false) {
				return Message::warning($errorMsg);
			}

			// 如果是充值订单的付款，则跳转到充值提交页面（暂不考虑合并付款的情况）
			if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_RECHARGE))) {

				// 暂不考虑合并付款的情况
				$tradeInfo = current($orderInfo['tradeList']);

				$this->params['payform'] = array('gateway' => Url::toRoute(['deposit/recharge'], true), 'method' => 'POST', 'params' => ['tradeNo' => $tradeInfo['tradeNo']]);

				$this->params['page'] = Page::seo(['title' => Language::get('cashier')]);
				return $this->render('../cashier.payform.html', $this->params);
			}

			// 如果是购买APP应用服务的订单，并且支付金额为0，则跳转到应用市场购物车页面（暂不考虑合并付款的的情况）
			if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_BUYAPP))) {

				// 暂不考虑合并付款的情况
				$tradeInfo = current($orderInfo['tradeList']);

				// 兼容处理
				if ($orderInfo['amount'] <= 0) {
					$bid = AppbuylogModel::find()->select('bid')->where(['userid' => Yii::$app->user->id, 'orderId' => $tradeInfo['bizOrderId']])->scalar();
					return $this->redirect(['appmarket/cashier', 'id' => $bid]);
				}
			}

			list($all_payments, $cod_payments, $errorMsg) = Plugin::getInstance('payment')->build()->getAvailablePayments($orderInfo, true, true);
			if ($errorMsg !== false) {
				return Message::warning($errorMsg);
			}

			if (!($depositAccount = DepositAccountModel::find()->select('account_id,account,money,frozen,pay_status,userid')->where(['userid' => Yii::$app->user->id])->asArray()->one())) {
				$depositAccount = ArrayHelper::toArray(DepositAccountModel::createDepositAccount(Yii::$app->user->id));
			}
			$this->params['deposit_account'] = $depositAccount;
			$this->params['payments'] = $all_payments;
			$this->params['orderInfo'] = $orderInfo;

			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.validate.js');

			$this->params['page'] = Page::seo(['title' => Language::get('cashier')]);
			return $this->render('../cashier.index.html', $this->params);
		} else {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if (empty($post->payment_code)) {
				return Message::warning(Language::get('pls_select_paymethod'));
			}

			// 如果是余额支付，验证支付密码
			if (in_array(strtolower($post->payment_code), array('deposit'))) {
				if (!DepositAccountModel::checkAccountPassword($post->password, Yii::$app->user->id)) {
					return Message::warning(Language::get('password_error'));
				}
			}

			// 获取交易数据
			list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($orderId, Yii::$app->user->id);
			if ($errorMsg !== false) {
				return Message::warning($errorMsg);
			}

			$payment = Plugin::getInstance('payment')->build();
			list($all_payments, $cod_payments, $errorMsg) = $payment->getAvailablePayments($orderInfo, true, true);
			if ($errorMsg !== false) {
				return Message::warning($errorMsg);
			}

			// 检查用户所使用的付款方式是否在允许的范围内
			if (!in_array($post->payment_code, $payment->getKeysOfPayments($all_payments))) {
				return Message::warning(Language::get('payment_not_available'));
			}

			// 买家选择的支付方式更新到交易表
			if (DepositTradeModel::updateTradePayment($orderInfo, $post->payment_code) === false) {
				return Message::warning(Language::get('payment_save_trade_fail'));
			}

			$payment_info = $all_payments[$post->payment_code];
			if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER))) {
				$isCod = strtoupper($post->payment_code) == 'COD';
				OrderModel::updateOrderPayment($orderInfo, $isCod ? $cod_payments : $payment_info, $isCod);
			}

			// 生成支付URL或表单
			list($payTradeNo, $payform) = Plugin::getInstance('payment')->build($post->payment_code, $post)->getPayform($orderInfo);
			if($payform['payResult'] === false) {
				return Message::warning($payform['errMsg']);
			}
			$this->params['payform'] = array_merge($payform, ['payTradeNo' => $payTradeNo]);

			// 跳转到真实收银台
			$this->params['page'] = Page::seo(['title' => Language::get('cashier')]);
			return $this->render('../cashier.payform.html', $this->params);
		}
	}

	/* 针对微信扫码支付，通过此返回结果实现自动跳转 */
	public function actionCheckpay()
	{
		if (($payTradeNo = Yii::$app->request->get('payTradeNo', 0))) {
			$tradeInfo = DepositTradeModel::find()->select('trade_id')->where(['buyer_id' => Yii::$app->user->id, 'payTradeNo' => $payTradeNo])->andWhere(['!=', 'status', 'PENDING'])->orderBy(['trade_id' => SORT_DESC])->one();

			// 由于支付变更，通过商户交易号找不到对应的交易记录后，插入的资金退回记录
			if (empty($tradeInfo)) {
				$tradeInfo = DepositTradeModel::find()->select('trade_id')->where(['buyer_id' => Yii::$app->user->id, 'tradeNo' => $payTradeNo])->andWhere(['!=', 'status', 'PENDING'])->orderBy(['trade_id' => SORT_DESC])->one();
			}
		}
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		return $tradeInfo ? true : false;
	}

	/* 微信获取支付二维码图片 */
	public function actionWxnativepay()
	{
		$qrCode = (new QrCode(Yii::$app->request->get('code')));
		//->setSize(250)
		//->setMargin(5)
		//->useForegroundColor(51, 153, 255);

		header('Content-Type: ' . $qrCode->getContentType());
		echo $qrCode->writeString();
	}
}
