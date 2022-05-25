<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\tenpay;

use yii;

use common\library\Language;
use common\library\Def;

use common\plugins\payment\tenpay\lib\ResponseHandler;
use common\plugins\payment\tenpay\lib\RequestHandler;
use common\plugins\payment\tenpay\lib\client\ClientResponseHandler;
use common\plugins\payment\tenpay\lib\client\TenpayHttpClient;

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
		$reqHandler->setGateUrl($this->gateway);
		
		//----------------------------------------
		//设置支付参数 
		//----------------------------------------
		$reqHandler->setParameter("partner", $this->partner);
		$reqHandler->setParameter("out_trade_no", $this->payTradeNo);
		$reqHandler->setParameter("total_fee", $orderInfo['amount'] * 100);  //总金额
		$reqHandler->setParameter("return_url", $this->returnUrl);
		$reqHandler->setParameter("notify_url", $this->notifyUrl);
		$reqHandler->setParameter("body", $orderInfo['title']);
		$reqHandler->setParameter("bank_type", "DEFAULT");  	  //银行类型，默认为财付通
		//用户ip
		$reqHandler->setParameter("spbill_create_ip", $_SERVER['REMOTE_ADDR']);//客户端IP
		$reqHandler->setParameter("fee_type", "1");               //币种
		$reqHandler->setParameter("subject", $orderInfo['title']);          //商品名称，（中介交易时必填）
		
		//系统可选参数
		$reqHandler->setParameter("sign_type", "MD5");  	 	  //签名方式，默认为MD5，可选RSA
		$reqHandler->setParameter("service_version", "1.0"); 	  //接口版本号
		$reqHandler->setParameter("input_charset", "utf-8");   	  //字符集
		$reqHandler->setParameter("sign_key_index", "1");    	  //密钥序号
		
		//业务可选参数
		$reqHandler->setParameter("trade_mode", 1);               //交易模式（1.即时到帐模式，2.中介担保模式，3.后台选择（卖家进入支付中心列
		
		/*
		$reqHandler->setParameter("attach", "");             	  //附件数据，原样返回就可以了
		$reqHandler->setParameter("product_fee", "");        	  //商品费用
		$reqHandler->setParameter("transport_fee", "0");      	  //物流费用
		$reqHandler->setParameter("time_start", date("YmdHis"));  //订单生成时间
		$reqHandler->setParameter("time_expire", "");             //订单失效时间
		$reqHandler->setParameter("buyer_id", "");                //买方财付通帐号
		$reqHandler->setParameter("goods_tag", "");               //商品标记
		$reqHandler->setParameter("transport_desc","");           //物流说明
		$reqHandler->setParameter("trans_type","1");              //交易类型
		$reqHandler->setParameter("agentid","");                  //平台ID
		$reqHandler->setParameter("agent_type","");               //代理模式（0.无代理，1.表示卡易售模式，2.表示网店模式）
		$reqHandler->setParameter("seller_id","");                //卖家的商户号
		*/
		
		//请求的URL
		//$reqUrl = $reqHandler->getRequestURL();
		$params = $reqHandler->getAllParameters();
		$result = array_merge($params, ['sign' => $this->getSign($params)]);

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
        if ($orderInfo['amount'] != $notify['total_fee']/100) 
		{
            // 支付的金额与实际金额不一致
            $this->errors = Language::get('price_inconsistent');
            return false;
        }
		
        //至此，说明通知是可信的，订单也是对应的，可信的
		if($notify['trade_mode'] == "1" && $notify['trade_state'] == "0") {
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
		$resHandler = new ResponseHandler();
		$resHandler->parameters = $notify;
		$resHandler->setKey($this->key);
		
		return $resHandler->isTenpaySign();
	}
	
	/* 查询通知是否有效 */
    public function queryNotify($notify)
    {
		// 创建支付应答对象
		$resHandler = new ResponseHandler();
		$resHandler->parameters = $notify;
		$resHandler->setKey($this->key);

		// 判断签名
		if($resHandler->isTenpaySign()) 
		{
			//通知id
			$notify_id = $resHandler->getParameter("notify_id");
		
			//通过通知ID查询，确保通知来至财付通
			//创建查询请求
			$queryReq = new RequestHandler();
			$queryReq->init();
			$queryReq->setKey($this->key);
			$queryReq->setGateUrl("https://gw.tenpay.com/gateway/simpleverifynotifyid.xml");
			$queryReq->setParameter("partner", $this->partner);
			$queryReq->setParameter("notify_id", $notify_id);
			
			//通信对象
			$httpClient = new TenpayHttpClient();
			$httpClient->setTimeOut(5);
			
			//设置请求内容
			$httpClient->setReqContent($queryReq->getRequestURL());
		
			//后台调用
			if($httpClient->call()) 
			{
				//设置结果参数
				$queryRes = new ClientResponseHandler();
				$queryRes->setContent($httpClient->getResContent());
				$queryRes->setKey($this->key);
			
				if($resHandler->getParameter("trade_mode") == "1"){ //  即时到帐
					//判断签名及结果（即时到帐）
					//只有签名正确,retcode为0，trade_state为0才是支付成功
					if($queryRes->isTenpaySign() && $queryRes->getParameter("retcode") == "0" && $resHandler->getParameter("trade_state") == "0") {
						return true;
					}
				}
			}
		}
		return false;
    }
}