<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\controllers;

use Yii;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;

/**
 * @Id SiteController.php 2018.11.20 $
 * @author mosir
 */

class SiteController extends \common\controllers\BaseMallController
{
	public function actionClosed()
	{
		if(Yii::$app->params['site_status']) {
			return $this->redirect(['default/index']);
		}
		return Message::warning(Yii::$app->params['closed_reason']);
	}
	
	public function actionError()
	{
		//echo 'error action config in main.php';
		exit('The page you want to visit does not exist');
	}
}