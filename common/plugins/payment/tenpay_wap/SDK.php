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

use common\library\Language;
use common\library\Def;

use common\plugins\payment\tenpay_wap\lib\ResponseHandler;
use common\plugins\payment\tenpay_wap\lib\RequestHandler;
use common\plugins\payment\tenpay_wap\lib\client\ClientResponseHandler;
use common\plugins\payment\tenpay_wap\lib\client\TenpayHttpClient;
use common\plugins\payment\tenpay_wap\lib\client\WapNotifyResponseHandler;

/**
 * @Id SDK.php 2018.7.24 $
 * @author mosir
 *
 */

class SDK
{
	/**
	 * 网关地址
	 * @var string $gateway
	 */
	public $gateway = null;

	/**
     * 支付插件实例
	 * @var string $code
	 */
	public $code = null;

	/**
	 * 商户ID
	 * @var string $partner
	 */
	public $partner = null;

	/**
	 * 商户密钥
	 * @var string $key
	 */
	public $key = null;

	/**
	 * 支付交易号
	 * @var string $payTradeNo
	 */
	public $payTradeNo;

	/**
	 * 通知地址
	 * @var string $notifyUrl
	 */
	public $notifyUrl;

	/**
	 * 返回地址
	 * @var string $returnUrl
	 */
	public $returnUrl;

	/**
	 * 抓取错误
	 */
	public $errors;

	/**
	 * 构造函数
	 */
	public function __construct(array $config)
	{
		foreach($config as $key => $value) {
            $this->$key = $value;
        }
	}
	
	public function getPayform($orderInfo = array(), $post = null)
    {
		// 创建支付请求对象
		$reqHandler = new RequestHandler();
		$reqHandler->init();
		$reqHandler->setKey($this->key);
		$reqHandler->setGateUrl("http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_init.cgi");

		//----------------------------------------
		//设置支付参数 
		//----------------------------------------
		$reqHandler->setParameter("bargainor_id", $this->partner);
		$reqHandler->setParameter("sp_billno", $this->payTradeNo);
		$reqHandler->setParameter("total_fee", $orderInfo['amount'] * 100);  //总金额
		$reqHandler->setParameter("callback_url", $this->returnUrl);
		$reqHandler->setParameter("notify_url", $this->notifyUrl);
		$reqHandler->setParameter("desc", $orderInfo['title']);
		$reqHandler->setParameter("bank_type", "0"); //银行类型，财付通填写0
		$reqHandler->setParameter("attach", $this->payTradeNo);
		$reqHandler->setParameter("ver", "2.0");//版本类型
		$reqHandler->setParameter("spbill_create_ip", $_SERVER['REMOTE_ADDR']);//客户端IP
		
		$httpClient = new TenpayHttpClient();
		$httpClient->setReqContent($reqHandler->getRequestURL());
		
		//后台调用
		if($httpClient->call())
		{
			//应答对象
			$resHandler = new ClientResponseHandler();
		
			$resHandler->setContent($httpClient->getResContent());
			//获得的token_id，用于支付请求
			$token_id = $resHandler->getParameter('token_id');
			$reqHandler->setParameter("token_id", $token_id);
			
			//请求的URL
			//$reqHandler->setGateUrl($this->gateway);
			//此次请求只需带上参数token_id就可以了，$reqUrl和$reqUrl2效果是一样的
			//$reqUrl = $reqHandler->getRequestURL(); 
			//$reqUrl = "http://wap.tenpay.com/cgi-bin/wappayv2.0/wappay_gate.cgi?token_id=".$token_id;
			
			if(!$token_id) exit($httpClient->getResContent());
			$params['token_id'] = $token_id;
			
			$result = $params;
		}
		return $result;
	}
	public function verifyNotify($orderInfo, $notify)
	{
		// 验证与本地信息是否匹配。这里不只是付款通知，有可能是发货通知，确认收货通知
        if ($orderInfo['payTradeNo'] != $notify['sp_billno'])
        {
            // 通知中的订单与欲改变的订单不一致
            $this->errors = Language::get('order_inconsistent');
            return false;
        }
        if ($orderInfo['amount'] != $notify['total_fee']/100) 
		{
            // 支付的金额与实际金额不一致
            $this->errors = Language::get('price_inconsistent');
            return false;
        }
		
        //至此，说明通知是可信的，订单也是对应的，可信的
		if($notify['pay_result'] == "0") {
			$order_status = Def::ORDER_ACCEPTED;
		}
		else {
			$this->errors = Language::get('undefined_status');
			return false;
		}
		return array('target' => $order_status);
	}
	
	/* 获取签名字符串 */
    public function getSign($params = array())
    {
        // 去除不参与签名的数据
        unset($params['sign']);

        // 排序
        ksort($params);
        reset($params);

        $sign  = '';
        foreach ($params as $key => $value)
        {
			if($value) {
            	$sign  .= "{$key}={$value}&";
			}
        }

        return strtoupper(md5(substr($sign, 0, -1) . '&key='.$this->key));
    }
	
	public function verifySign($notify)
    {
		// 创建支付应答对象
		$resHandler = new WapNotifyResponseHandler();
		$resHandler->parameters = $notify;
		$resHandler->setKey($this->key);
		
		return $resHandler->isTenpaySign();
	}
}