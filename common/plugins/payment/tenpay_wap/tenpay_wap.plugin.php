<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\tenpay_wap;

use yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\library\Language;

use common\plugins\BasePayment;
use common\plugins\payment\tenpay_wap\SDK;

/**
 * @Id tenpay_wap.plugin.php 2018.7.24 $
 * @author mosir
 */

class Tenpay_wap extends BasePayment
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = 'http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi';

    /**
     * 支付插件实例
	 * @var string $code
	 */
	protected $code = 'tenpay_wap';
	
	/**
     * SDK实例
	 * @var object $client
     */
	private $client = null;
	
	/* 获取支付表单 */
	public function getPayform(&$orderInfo = array(), $redirect = true)
    {
		// 支付网关商户订单号
		$payTradeNo = parent::getPayTradeNo($orderInfo);
		
		// 给其他页面使用
		foreach($orderInfo['tradeList'] as $key => $value) {
			$orderInfo['tradeList'][$key]['payTradeNo'] = $payTradeNo;
		}

		$sdk = $this->getClient();
		$sdk->gateway = $this->gateway;
		$sdk->code = $this->code;
		$sdk->payTradeNo = $payTradeNo;
		$sdk->notifyUrl = $this->createNotifyUrl($payTradeNo);
		$sdk->returnUrl = $this->createReturnUrl($payTradeNo);
	
		$params = $this->createPayform($sdk->getPayform($orderInfo), $this->gateway, 'get');
        return array($payTradeNo, $params);
    }
	
	/* 获取通知地址（不支持带参数，另：因为电脑支付和手机支付参数一致，所以可以使用相同的通知地址） */
    public function createNotifyUrl($payTradeNo = '')
    {
        return Url::toRoute(['paynotify/tenpay'], true);
    }

    /* 返回通知结果 */
    public function verifyNotify($orderInfo, $strict = false)
    {
        if (empty($orderInfo)) {
			$this->errors = Language::get('order_info_empty');
            return false;
        }
		
		$notify = $this->getNotify();

        // 验证通知是否可信
        if (!($sign_result = $this->verifySign($notify, $strict)))
        {
            // 若本地签名与网关签名不一致，说明签名不可信
            $this->errors = Language::get('sign_inconsistent');
            return false;
        }
		
		$sdk = $this->getClient();
		if(!($result = $sdk->verifyNotify($orderInfo, $notify))) {
			$this->errors = $sdk->errors;
            return false;
		}
		return $result;
    }

    /* 验证签名是否可信 */
    private function verifySign($notify, $strict = false)
    {
		$sdk = $this->getClient();
		
		// 验证签名
		if($strict == true) {
			return $sdk->verifySign($notify);
		}
		return true;
    }
	
	public function verifyResult($result = false) {
		return parent::verifyResult($result);
	} 
	
	public function getNotifySpecificData() {
		$notify = $this->getNotify();
		return array($notify['total_fee']/100, $notify['transaction_id']);
	}

	/**
     * 获取SDK实例
     */
    public function getClient()
    {
        if($this->client === null) {
            $this->client = new SDK($this->config);
        }
        return $this->client;
    }
}