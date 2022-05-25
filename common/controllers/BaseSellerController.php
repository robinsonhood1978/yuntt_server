<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\controllers;

use Yii;

/**
 * @Id BaseSellerController.php 2018.10.20 $
 * @author mosir
 */

class BaseSellerController extends BaseUserController
{
	public function init() 
	{
		parent::init();
		Yii::$app->session->set('userRole', 'seller');
	}

	/**
	 * 在执行Action前，判断是否有权限访问
	 * @param $action
	 */
	public function beforeAction($action)
    {
		if(!$this->checkAccess($action)) {
			return false;
		}
		return parent::beforeAction($action);
	}

	/**
	 * 在执行Action前，判断是否有权限访问
	 * @param $action
	 */
	public function checkAccess($action)
	{
		if($this->visitor['store_id'] > 0 && (Yii::$app->user->id == $this->visitor['store_id'])) {
			return true;
		}
		return $this->accessWarning();
	}
}