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

use common\library\Page;

/**
 * @Id BaseUserController.php 2018.10.20 $
 * @author mosir
 */

class BaseUserController extends BaseMallController
{
	public function init() 
	{
		parent::init();
		Yii::$app->session->set('userRole', 'buyer');
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
		// 排除允许游客访问的页面
		if($this->extraAction && in_array($action->id, $this->extraAction)) {
			return true;
		}

		if(Yii::$app->user->isGuest) {
			Page::redirect(Yii::$app->request->url);
		 	return false;
		 }
		 return true;
	}
	
	/**
	 * 用户中心栏目菜单 
	 * @return array
	 */
    public function getUserSubmenu()
    {
        return array();
    }
}