<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\alipay_app;

use yii;
use yii\helpers\Url;

use common\library\Language;

use common\plugins\BasePayment;
use common\plugins\payment\alipay_app\SDK;

/**
 * @Id alipay_app.plugin.php 2018.6.3 $
 * @author mosir
 */

class Alipay_app extends BasePayment
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	protected $gateway = 'https://openapi.alipay.com/gateway.do';

    /**
     * 支付插件实例
	 * @var string $code
	 */
	protected $code = 'alipay_app';
	
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
		//$sdk->returnUrl = $this->createReturnUrl($payTradeNo);
	
		$params = ['orderInfo' => $sdk->getPayform($orderInfo)];
        return array($payTradeNo, $params);
    }
	
	/* 获取通知地址（不支持带参数，另：因为电脑支付和手机支付参数一致，所以可以使用相同的通知地址） */
    public function createNotifyUrl($payTradeNo = '')
    {
        return Url::toRoute(['paynotify/alipay'], true);
    }

    /* 返回通知结果 */
    public function verifyNotify($orderInfo, $strict = false)
    {
        if (empty($orderInfo)) {
			$this->errors = Language::get('order_info_empty');
            return false;
        }
		
		$notify = $this->getNotify();
		
		// 预留，如果发现签名失败，可考虑此
		$notify['fund_bill_list'] = stripslashes($notify['fund_bill_list']);

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
		// 验证签名
		if($strict == true) {
			return $this->getClient()->verifySign($notify);
		}
		return true;
    }
	
	public function verifyResult($result = false) {
		return parent::verifyResult($result);
	} 
	
	public function getNotifySpecificData() {
		$notify = $this->getNotify();
		return array($notify['total_amount'], $notify['trade_no']);
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