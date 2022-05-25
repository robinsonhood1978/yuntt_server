<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\wxpay;

use yii;

use common\library\Language;
use common\library\Def;

use common\plugins\payment\wxpay\lib\JsApi_pub;
use common\plugins\payment\wxpay\lib\UnifiedOrder_pub;
use common\plugins\payment\wxpay\lib\Notify_pub;

/**
 * @Id SDK.php 2018.7.19 $
 * @author mosir
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
	 * 插件配置信息
	 * @var array $config
	 */
	private $config;

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
		$this->config = $config;
	}
	
	public function getPayform($orderInfo = array(), $post = null)
	{
		$jsApi = new JsApi_pub($this->config);
		return $jsApi->createOauthUrlForCode($this->returnUrl);
	}
	
	public function getParameters($wxcode, $orderInfo, $payTradeNo = '')
    {
		$jsApi = new JsApi_pub($this->config);
		
		$jsApi->setCode($wxcode);
		$openid = $jsApi->getOpenId(); // 获取openid https://pay.weixin.qq.com/wiki/doc/api/jsapi.php?chapter=4_4
		
		if(!$openid) {
			$this->errors = Language::get('openid_empty');
			return false;
		}
		
		// 使用统一支付接口
		$unifiedOrder = new UnifiedOrder_pub($this->config);
		
		// body max length <= 128
		if(strlen($orderInfo['title']) > 128) {
			$body = mb_substr($orderInfo['title'],0,40, Yii::$app->charset);// 代表40个字 120个字符
		} else $body = $orderInfo['title'];
	
		// 设置统一支付接口参数
		$unifiedOrder->setParameter("openid", $openid);
		$unifiedOrder->setParameter("body", $body);//商品描述
	
		$unifiedOrder->setParameter("out_trade_no", $payTradeNo);//商户订单号 
		$unifiedOrder->setParameter("total_fee", $orderInfo['amount'] * 100);//总金额
		$unifiedOrder->setParameter("notify_url", $this->notifyUrl);//通知地址 
		$unifiedOrder->setParameter("trade_type","JSAPI");//交易类型
		
		$prepay_id = $unifiedOrder->getPrepayId();
		$jsApi->setPrepayId($prepay_id);
		$jsApiParameters = $jsApi->getParameters();
		
		return $jsApiParameters;
	}
	public function verifyNotify($orderInfo, $notify)
	{
		// 验证与本地信息是否匹配。这里不只是付款通知，有可能是发货通知，确认收货通知
        if ($orderInfo['payTradeNo'] != $notify['out_trade_no'])
        {
            // 通知中的订单与欲改变的订单不一致
            $this->errors = Language::get('order_inconsistent');
            return false;
        }
        if ($orderInfo['amount'] != round($notify['total_fee']/100,2))
		{
            // 支付的金额与实际金额不一致
            $this->errors = Language::get('price_inconsistent');
            return false;
        }
	
        //至此，说明通知是可信的，订单也是对应的，可信的
		if(($notify['return_code'] == 'SUCCESS') && ($notify['result_code'] == 'SUCCESS')) {
			$order_status = Def::ORDER_ACCEPTED;
		} else {
			$this->errors = Language::get('undefined_status');
			return false;
		}
	
		return array('target' => $order_status);
	}
	
	public function verifySign($notify)
    {
		$notify_pub = new Notify_pub($this->config);
		
		unset($notify['payTradeNo']);
		$notify_pub->data = $notify;
		
		return $notify_pub->checkSign();
	}
	
	public function verifyResult($result) 
    {
		$notify= new Notify_pub($this->config);
		
        if ($result)
        {
			$notify->setReturnParameter("return_code","SUCCESS");//设置返回码	
        }
		else
		{
			$notify->setReturnParameter("return_code","FAIL");//返回状态码
			$notify->setReturnParameter("return_msg","SIGNATURE FAIL");//返回信息
		}
		
		//回应微信
		$returnXml = $notify->returnXml();
		echo $returnXml;
    }
}