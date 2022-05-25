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

use common\models\DepositTradeModel;

use common\library\Plugin;
use common\library\Def;

/**
 * @Id PaynotifyController.php 2018.12.20 $
 * @author mosir
 */

class PaynotifyController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 当异步通知Url不可以带参数时使用此
	 */
	public function actionAlipay()
	{
		return $this->notify(Yii::$app->request->post('out_trade_no', 0));
	}
	
	/**
	 * 当异步通知Url不可以带参数时使用此
	 */
	public function actionTenpay()
	{
		return $this->notify(Yii::$app->request->post('out_trade_no', 0));
	}
	
	/**
	 * 当异步通知Url不可以带参数时使用此
	 */
	public function actionWxpay()
	{
		$xml = isset($GLOBALS['HTTP_RAW_POST_DATA']) ? $GLOBALS['HTTP_RAW_POST_DATA'] : file_get_contents("php://input");
		libxml_disable_entity_loader(true);	
		$notify = json_decode(json_encode(simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA)), true);
		return $this->notify($notify['out_trade_no']);
	}
	
	/**
	 * 当异步通知Url不可以带参数时使用此
	 */
	public function actionUnionpay()
	{
		return $this->notify(Yii::$app->request->post('out_trade_no', 0));
	}

	/**
	 * 当异步通知Url不可以带参数时使用此
	 */
	public function actionLatipay()
	{
		return $this->notify(Yii::$app->request->post('order_id', 0));
	}
	
	/**
	 * 当异步通知Url可以带参数时可使用此
	 */
	public function actionNotify()
	{
		return $this->notify(Yii::$app->request->post('payTradeNo', 0));
	}
	private function notify($payTradeNo)
	{
		if(empty($payTradeNo)) {
			return false;
		}
		if(!($orderInfo = DepositTradeModel::getTradeInfoForNotify($payTradeNo))) {
			return false;
		}
		
		$payment_code = $orderInfo['payment_code'];
		
		// 货到付款的订单不许进入此通知页面
        if(in_array(strtoupper($payment_code), array('COD'))) {
			return false;
		}
		
		$payment = Plugin::getInstance('payment')->build($payment_code);
		if(!($payment_info = $payment->getInfo()) || !$payment_info['enabled']) {
			return false;
        }
		
		if(($notify_result = $payment->verifyNotify($orderInfo, true)) === false) {
			return $payment->verifyResult(false);
		}
		
		// 当支付结果通知验证成功后，说明买家已经实际支付了款项，那么处理业务逻辑 
		list($notifyMoney, $outTradeNo) = $payment->getNotifySpecificData();
		
		// 将买家在支付网关支付的钱（兼容处理充值的订单），充值到余额账户里（增加收支记录，变更账户余额）
		if(!$payment->handleRechargeAfterNotify($orderInfo, $notify_result, $outTradeNo)) {
			return $payment->verifyResult(false);
		}	
			
		// 购物订单（处理购物逻辑）
		if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER)))
		{
			if($payment->handleOrderAfterNotify($orderInfo, $notify_result) === false) {
				//return Message::warning($payment->errors);
				return $payment->verifyResult(false);
			}
		}	
		
		// 购买应用订单（处理购买应用逻辑）
		if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_BUYAPP)))
		{
			if($payment->handleBuyappAfterNotify($orderInfo, $notify_result) === false) {
				//return Message::warning($payment->errors);
				return $payment->verifyResult(false);
			}
		}
		
		return $payment->verifyResult(true);
	}
}