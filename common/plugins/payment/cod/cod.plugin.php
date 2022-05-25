<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\cod;

use yii;
use yii\helpers\Url;

use common\models\OrderModel;
use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

use common\plugins\BasePayment;

/**
 * @Id cod.plugin.php 2018.7.24 $
 * @author mosir
 */
class Cod extends BasePayment
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = null;

    /**
     * 支付插件实例
	 * @var string $code
	 */
	protected $code = 'cod';
	
	/* 获取支付表单 */
    public function getPayform(&$orderInfo = array(), $redirect = true)
    {
		// 支付网关商户订单号
		$payTradeNo = parent::getPayTradeNo($orderInfo);
		
		// 给其他页面使用
		foreach($orderInfo['tradeList'] as $key => $value) {
			$orderInfo['tradeList'][$key]['payTradeNo'] = $payTradeNo;
		}
		
		// 因为是货到付款，所以直接处理业务
		if($this->payNotify($orderInfo, $payTradeNo) === false) {
			($this->errors === null) && $this->errors = Language::get('pay_fail');
			return false;
		}
		
		// 处理完业务后，显示结果通知页面
		$this->gateway = Url::toRoute(['paynotify/index'], true);
		$params = $this->createPayform(['payTradeNo' => $payTradeNo], $this->gateway, 'get');
        return array($payTradeNo, $params);
	}
	
	public function payNotify($orderInfo = array(), $payTradeNo = '')
	{
		if(empty($payTradeNo)) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}
		if(!($orderInfo = DepositTradeModel::getTradeInfoForNotify($payTradeNo))) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}
	
		if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER)) && ($orderInfo['payment_code'] == 'cod')) 
		{
			// 实际上只有一次循环
			foreach($orderInfo['tradeList'] as $tradeInfo)
			{
				// 修改交易状态为提交
				$model = DepositTradeModel::find()->where(['tradeNo' => $tradeInfo['tradeNo'], 'status' => 'PENDING'])->one();
				$model->status = 'SUBMITTED';
				if($model->update()) {
					OrderModel::updateAll(['status' => Def::ORDER_SUBMITTED], ['status' => Def::ORDER_PENDING, 'order_sn' => $tradeInfo['bizOrderId']]);
				}
				
				$orderInfo = $tradeInfo['order_info'];
				
				// 邮件提醒：	订单已确认，等待安排发货
				Basewind::sendMailMsgNotify($orderInfo, array(
						'receiver' 	=> $orderInfo['buyer_id'],
						'key' => 'tobuyer_confirm_cod_order_notify',
					)
				);
			}
		}
		return true;
	}
}