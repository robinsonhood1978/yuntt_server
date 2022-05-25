<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id SiteController.php 2018.11.20 $
 * @author mosir
 */

class SiteController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		// parent::init(); don't init

		$this->view  = Page::setView('mall');
		$this->params = [
			'homeUrl'		=> Basewind::homeUrl(),
			'siteUrl' 		=> Basewind::siteUrl(),
			'lang' 			=> Language::find($this->id),
		];
	}

	public function actionClosed()
	{
		if(Yii::$app->params['site_status']) {
			// don't use $this->redirect()
			return Yii::$app->getResponse()->redirect(['default/index']);
		}
		return Message::warning(Yii::$app->params['closed_reason']);
	}
	
	public function actionError()
	{
		//echo 'error action config in main.php';
		exit('The page you want to visit does not exist');
	}
}