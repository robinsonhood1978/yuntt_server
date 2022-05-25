<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\payment\wxmppay\lib;

use common\plugins\payment\wxmppay\lib\Common_util_pub;
use common\plugins\payment\wxmppay\lib\WxPayConf_pub;

/**
 * @Id Mp_pub.php 2018.6.3 $
 * @author mosir
 *
 */

class Mp_pub extends Common_util_pub
{
	var $code;//code码，用以获取openid
	var $openid;//用户的openid
	var $parameters;//app参数，格式为数组
	var $prepay_id;//使用统一支付接口得到的预支付id
	var $partnerid;
	var $curl_timeout;//curl超时时间
	
	function __construct($config) 
	{
		parent::__construct($config);
	
		//设置curl超时时间
		$this->curl_timeout = WxPayConf_pub::CURL_TIMEOUT;
	}

	/**
	 * 	作用：生成可以获得openid的url
	 */
	function createOauthUrlForOpenid()
	{
		$urlObj["appid"] = $this->_config['AppID'];
		$urlObj["secret"] = $this->_config['AppSecret'];
		$urlObj["js_code"] = $this->code;
		$urlObj["grant_type"] = "authorization_code";
		$bizString = $this->formatBizQueryParaMap($urlObj, false);
		return "https://api.weixin.qq.com/sns/jscode2session?".$bizString;
	}
	
	/**
	 * 	作用：通过curl向微信提交code，以获取openid
	 */
	function getOpenid()
	{
		$url = $this->createOauthUrlForOpenid();
        //初始化curl
       	$ch = curl_init();
		//设置超时
		//curl_setopt($ch, CURLOP_TIMEOUT, $this->curl_timeout);
		curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,FALSE);
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,FALSE);
		curl_setopt($ch, CURLOPT_HEADER, FALSE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
		//运行curl，结果以jason形式返回
        $res = curl_exec($ch);
		curl_close($ch);
		//取出openid
		$data = json_decode($res,true);
		$this->openid = $data['openid'];
		return $this->openid;
	}

	/**
	 * 	作用：设置prepay_id
	 */
	function setPrepayId($prepayId)
	{
		$this->prepay_id = $prepayId;
	}

	/**
	 * 	作用：设置Partner_id
	 */
	function setPartnerId($partnerid) 
	{
		$this->partnerid = $partnerid;
	}

	/**
	 * 	作用：设置code
	 */
	function setCode($code_)
	{
		$this->code = $code_;
	}

	/**
	 * 	作用：设置mp的参数
	 */
	public function getParameters()
	{
		$jsApiObj["appId"] = $this->_config['AppID'];
		$timeStamp = time();
	    $jsApiObj["timeStamp"] = "$timeStamp";
	    $jsApiObj["nonceStr"] = $this->createNoncestr();
		$jsApiObj["package"] = "prepay_id=$this->prepay_id";
	    $jsApiObj["signType"] = "MD5";
	    $jsApiObj["paySign"] = $this->getSign($jsApiObj);
	    $this->parameters = $jsApiObj;
		
		return $this->parameters;
	}
}