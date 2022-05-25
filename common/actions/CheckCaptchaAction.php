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
use yii\helpers\ArrayHelper;

use common\library\Basewind;

/**
 * @Id CheckCaptchaAction.php 2018.3.5 $
 * @author mosir
 */

class CheckCaptchaAction extends Action
{
	/**
     * Runs the action.
     */
    public function run()
    {
		Yii::$app->response->format = Response::FORMAT_JSON;
		if(Basewind::checkCaptcha(Yii::$app->request->get('captcha', ''))) {
			return true;
		} return false;
	}
}