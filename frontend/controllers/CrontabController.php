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
/**
 * @Id CrontabController.php 2018.3.1 $
 * @author mosir
 */

class CrontabController extends \common\controllers\BaseMallController
{
	/**
	 * 使用Url执行计划任务
	 * Url::toRoute(['crontab/index'])
	 */
	public function actionIndex() 
	{
		\common\library\Taskqueue::run();
	}
}
