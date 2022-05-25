<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

/**
 * @Id SiteController.php 2018.11.20 $
 * @author yxyc
 */

class SiteController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;

	public function actionError()
	{
		return \yii\helpers\Json::encode(['message' => 'The page you want to visit does not exist']);
	}
}