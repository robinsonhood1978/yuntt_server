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

use common\library\Def;
use common\library\Language;

use common\plugins\payment\alipay_app\lib\AopClient;
use common\plugins\payment\alipay_app\lib\request\AlipayTradeAppPayRequest;

/**
 * @Id SDK.php 2018.7.19 $
 * @author mosir
 *
 * docs: 手机网站支付 https://docs.open.alipay.com/203/107090/
 * docs: 电脑网站支付 https://docs.open.alipay.com/270/105900/
 * docs: APP支付 https://opendocs.alipay.com/open/204/105297/
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
	 * @var string $appId
	 */
	public $appId = null;

	/**
	 * 支付宝公钥
	 * @var string $alipayrsaPublicKey
	 */
	public $alipayrsaPublicKey = null;

	/**
	 * 应用公钥
	 * @var string $rsaPublicKey
	 */
	public $rsaPublicKey = null;

	/**
	 * 应用私钥
	 * @var string $rsaPrivateKey
	 */
	public $rsaPrivateKey = null;

	/**
	 * 签名类型
	 * @var string $signType
	 */	
	public $signType = 'RAS2';

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
		$aop = new AopClient();
		$aop->appId 				= $this->appId;
		$aop->rsaPrivateKey 		= $this->rsaPrivateKey;
		$aop->alipayrsaPublicKey 	= $this->alipayrsaPublicKey;
		$aop->postCharset 			= Yii::$app->charset;
		$aop->signType 				= $this->signType;
		
		$biz_content = array(
			'subject'       => $orderInfo['title'],
			'out_trade_no'  => $this->payTradeNo,
			'total_amount'  => $orderInfo['amount'],
			'product_code'	=> 'QUICK_MSECURITY_PAY'
		);
 		$request = new AlipayTradeAppPayRequest();
		$request->setBizContent(json_encode($biz_content));
		//$request->setReturnUrl($this->returnUrl);
		$request->setNotifyUrl($this->notifyUrl);
		$result = $aop->sdkExecute($request);
		
		return $result;
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
        if ($orderInfo['amount'] != $notify['total_amount']) 
		{
            // 支付的金额与实际金额不一致
            $this->errors = Language::get('price_inconsistent');
            return false;
        }
		
        //至此，说明通知是可信的，订单也是对应的，可信的
		if(in_array($notify['trade_status'], ['TRADE_FINISHED','TRADE_SUCCESS'])) {
			$order_status = Def::ORDER_ACCEPTED;
		}
		elseif(in_array($notify['trade_status'], ['TRADE_CLOSED'])) {
			$order_status = Def::ORDER_CANCELED;
		}
		else {
			$this->errors = Language::get('undefined_status');
			return false;
		}
		return array('target' => $order_status);
	}
	
	public function verifySign($notify)
    {
		$aop = new AopClient();
		//$aop->appId 				= $this->appId;
		//$aop->rsaPrivateKey 		= $this->rsaPrivateKey;
		$aop->alipayrsaPublicKey 	= $this->alipayrsaPublicKey;
		//$aop->postCharset 		= Yii::$app->charset;
		//$aop->signType 			= $this->signType;
		return $aop->rsaCheckV1($notify, $this->alipayrsaPublicKey, $this->signType);
	}
}