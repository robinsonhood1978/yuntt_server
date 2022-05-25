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
use yii\helpers\ArrayHelper;

use common\models\UserPrivModel;

use common\library\Page;
use backend\library\Menu;

/**
 * @Id BaseAdminController.php 2018.10.20 $
 * @author mosir
 */

class BaseAdminController extends BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView();
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
		$this->params = ArrayHelper::merge($this->params, ['back_nav' => Menu::getMenus()]);
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

		 // 栏目权限控制
		 if(!$this->checkPrivs($action)) {
			$params = ['back_nav' => Menu::getMenus()];
			return $this->accessWarning($params);
		 }

		 return true;
	}

	/**
	 * 模块权限控制
	 * @param $action
	 */
	private function checkPrivs($action) 
	{
		// 判断是不是管理员
		if(UserPrivModel::isManager(Yii::$app->user->id))
		{
			// 判断有没有该页面访问权限
			if(UserPrivModel::accessPage($action->controller->id, $action->id, Yii::$app->user->id, 0)) {
				return true;
			}
		}
		return false;
	}
}