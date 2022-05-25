<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\deposit;


use yii;
use yii\helpers\Url;

use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

use common\plugins\BasePayment;

/**
 * @Id deposit.plugin.php 2018.7.23 $
 * @author mosir
 */

class Deposit extends BasePayment
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
	protected $code = 'deposit';

	/* 获取支付表单 */
	public function getPayform(&$orderInfo = array(), $redirect = true)
	{
		// 支付网关商户订单号
		$payTradeNo = parent::getPayTradeNo($orderInfo);

		// 给其他页面使用
		foreach ($orderInfo['tradeList'] as $key => $value) {
			$orderInfo['tradeList'][$key]['payTradeNo'] = $payTradeNo;
		}

		// 因为是余额支付，所以直接处理业务
		if ($this->payNotify($orderInfo, $payTradeNo) === false) {
			return array($payTradeNo, ['payResult' => false, 'errMsg' => $this->errors ? $this->errors : Language::get('pay_fail')]);
		}

		// 处理完业务后，显示结果通知页面
		if (Basewind::getCurrentApp() != 'api') {
			$this->gateway = Url::toRoute(['paynotify/index'], true);

			$params = ['payTradeNo' => $payTradeNo];
			if (!Yii::$app->urlManager->enablePrettyUrl) {
				$params['r'] = 'paynotify/index';
			}
			$params = $this->createPayform($params, $this->gateway, 'get');
		}
		
		return array($payTradeNo, $params ? $params : ['payResult' => true]);
	}

	public function payNotify($orderInfo = array(), $payTradeNo = '')
	{
		if (empty($payTradeNo)) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}
		if (!($orderInfo = DepositTradeModel::getTradeInfoForNotify($payTradeNo))) {
			$this->errors = Language::get('order_info_empty');
			return false;
		}

		if (in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER))) {
			return parent::handleOrderAfterNotify($orderInfo, ['target' => Def::ORDER_ACCEPTED]);
		} elseif (in_array($orderInfo['bizIdentity'], array(Def::TRADE_BUYAPP))) {
			return parent::handleBuyappAfterNotify($orderInfo, ['target' => Def::ORDER_ACCEPTED]);
		}
		return true;
	}
}
