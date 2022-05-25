<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\actions;

use Yii;
use yii\base\Action;
use yii\web\Response;

/**
 * @Id SetCookieAction.php 2018.5.3 $
 * @author mosir
 */

class SetCookieAction extends Action
{
    public function run()
    {
		$cookies = Yii::$app->response->cookies;
		
		// 在要发送的响应中添加一个新的 cookie
		$cookies->add(new \yii\web\Cookie([
			'name' =>  Yii::$app->request->get('name'),
			'value' => Yii::$app->request->get('value'),
		]));
		Yii::$app->response->send();
	}
}