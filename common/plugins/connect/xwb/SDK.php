<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\connect\xwb;

use yii;

use common\plugins\connect\xwb\lib\SaeTOAuthV2;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK
{
	/**
	 * 插件网关
	 * @param string $gateway
	 */
	protected $gateway = null;

	/**
	 * @param string $WB_AKEY
	 */
	public $WB_AKEY = null;

	/**
	 * @param string $WB_SKEY
	 */
	public $WB_SKEY;

	/**
	 * 返回地址
	 * @param string $redirect_uri
	 */
	public $redirect_uri = null;
	
	/**
	 * 构造函数
	 */
	public function __construct(array $config)
	{
		foreach($config as $key => $value) {
            $this->$key = $value;
        }
	}
	
	public function getAccessToken($code = '')
	{
		if($code) 
		{
			$o = new SaeTOAuthV2($this->WB_AKEY, $this->WB_SKEY);
			$token = $o->getAccessToken('code', array('code' => $code, 'redirect_uri' => $this->redirect_uri));
			if(isset($token['access_token'])) {
				$token['unionid'] = $token['access_token'];
			}

			return (object) $token;
		}

		return false;
	}
	
	public function getUserInfo($resp = null)
	{
		return false;
	}
	
	public function getAuthorizeURL()
	{
		$o = new SaeTOAuthV2($this->WB_AKEY, $this->WB_SKEY);
		return $o->getAuthorizeURL($this->redirect_uri);
	}
}