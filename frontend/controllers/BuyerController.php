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
 * @Id BuyerController.php 2018.4.1 $
 * @author mosir
 */

class BuyerController extends \common\controllers\BaseUserController
{
    public function actionIndex()
    {
		Yii::$app->session->set('userRole', 'buyer');
		return $this->redirect(['buyer_order/index']);
	}
}