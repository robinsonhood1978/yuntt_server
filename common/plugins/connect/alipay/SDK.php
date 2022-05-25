<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\connect\alipay;

use yii;

use common\library\Basewind;

use common\plugins\connect\alipay\lib\AopClient;
use common\plugins\connect\alipay\lib\SignData;
use common\plugins\connect\alipay\lib\request\AlipaySystemOauthTokenRequest;
use common\plugins\connect\alipay\lib\request\AlipayUserInfoShareRequest;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK
{
	/**
	 * 插件网关
	 * @var string $gateway
	 */
	protected $gateway = 'https://openapi.alipay.com/gateway.do';

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
	 * 版本号
	 * @var string $version
	 */
	public $version = '1.0';

	/**
	 * 数据格式
	 * @var string $format
	 */
	public $format = 'json';
	
	/**
	 * 构造函数
	 */
	public function __construct(array $config)
	{
		foreach($config as $key => $value) {
            $this->$key = $value;
        }
	}

	/**
	 * 换取授权访问令牌
	 * alipay.system.oauth.token
	 */
	public function getAccessToken($code = '')
	{
		$response = false;
		
		if($code) 
		{
			$aop = new AopClient();
			$aop->gatewayUrl = $this->gateway;
			$aop->appId = $this->appId;
			$aop->rsaPrivateKey = $this->rsaPrivateKey;
			$aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
			$aop->apiVersion = $this->version;
			$aop->signType = $this->signType;
			$aop->postCharset = Yii::$app->charset;
			$aop->format = $this->format;
			$request = new AlipaySystemOauthTokenRequest();
			$request->setGrantType("authorization_code");
			$request->setCode($code);
			//$request->setRefreshToken("上一次的access_token"); // 可选
			$result = $aop->execute($request); 
			
			/**
			 * responseNode （字段可能有变化，请参照具体的返回值）
			 * user_id: "2088102150477652",
			 * access_token: "20120823ac6ffaa4d2d84e7384bf983531473993",
			 * expires_in: "3600",
			 * refresh_token: "20120823ac6ffdsdf2d84e7384bf983531473993",
			 * re_expires_in: "3600"
			 * @link: https://docs.open.alipay.com/api_9/alipay.system.oauth.token
			*/
			$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
			//$resultCode = $result->$responseNode->code;
			//if(!empty($resultCode) && $resultCode == 10000){
			if($result->$responseNode->access_token) {
				$response = $result->$responseNode;
			}
			$response->unionid = $response->user_id;
		}
		return $response;
	}
	
	/**
	 * 支付宝会员授权信息查询接口
	 * alipay.user.info.share
	 */
	public function getUserInfo($resp = null)
	{
		$response = false;
		
		if($resp->access_token) 
		{
			$aop = new AopClient();
			$aop->gatewayUrl = $this->gateway;
			$aop->appId = $this->appId;
			$aop->rsaPrivateKey = $this->rsaPrivateKey;
			$aop->alipayrsaPublicKey = $this->alipayrsaPublicKey;
			$aop->apiVersion = $this->version;
			$aop->signType = $this->signType;
			$aop->postCharset = Yii::$app->charset;
			$aop->format = $this->format;
			$request = new AlipayUserInfoShareRequest();
			$result = $aop->execute ( $request , $resp->access_token ); 
			
			/**
			 * responseNode （字段可能有变化，请参照具体的返回值）
			 * user_id: "2088102104794936",
			 * avatar: "http://tfsimg.alipay.com/images/partner/T1uIxXXbpXXXXXXXX",
			 * nick_name: "支付宝小二",
			 * gender: "F"
			 * @link: https://docs.open.alipay.com/api_2/alipay.user.info.share
			*/
			$responseNode = str_replace(".", "_", $request->getApiMethodName()) . "_response";
			$resultCode = $result->$responseNode->code;
			if(!empty($resultCode) && $resultCode == 10000){
				$response = $result->$responseNode;
			}
		}
		return $response;
	}
}