<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\connect\apple;

use yii;
use yii\helpers\Url;

use common\library\Language;

use common\plugins\BaseConnect;

/**
 * @Id apple.plugin.php 2019.6.6 $
 * @author mosir
 */

class Apple extends BaseConnect
{
	/**
	 * 插件网关
	 * @var string $gateway
	 */
	protected $gateway = '';
	
	/**
	 * 插件实例
	 * @var string $code
	 */
	protected $code = 'apple';
	
	/**
     * SDK实例
	 * @var object $client
     */
	private $client = null;

	/**
	 * 用户编号
	 * @var int $userid
	 */
	public $userid;
	
	public function login($redirect = true)
	{
		
	}
	
	public function callback($autobind = false)
	{
		$response = $this->params->unionid ? $this->params : $this->getAccessToken();
		if(!$response) {
			return false;
		}

		// 已经绑定
		if(($userid = parent::isBind($response->unionid))) {
			$this->userid = $userid;
			return true;
		}

		// 没有绑定，自动绑定
		if($autobind) {
			if(($identity = parent::autoBind($this->getUserInfo($response)))) {
				$this->userid = $identity->userid;
				return true;
			}
			return false;
		}

		// 跳转到绑定页面
		return parent::goBind($this->getUserInfo($response));
	}

	/**
	 * 通过CODE获取用户信息
	 */
	public function getAccessToken()
	{
		return false;
	}
	
	public function getUserInfo($response = null)
	{
		return $response;
	}
}