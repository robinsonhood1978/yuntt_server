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
use yii\helpers\Url;

use common\library\Page;

/**
 * @Id BaseInstallController.php 2018.10.20 $
 * @author mosir
 */

class BaseInstallController extends BaseMallController
{
	/**
	 * Yii2内核升级后(> 2.0.6)
	 * 在安装控制器中必须使用 getResponse() 才能正确跳转
	 * 原因是安装程序控制器没有执行init()方法导致
	 */
	public function redirect($url, $statusCode = 302)
    {
		return Yii::$app->getResponse()->redirect(Url::to($url), $statusCode);
    }
}