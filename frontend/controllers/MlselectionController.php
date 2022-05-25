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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;

/**
 * @Id MlselectionController.php 2018.4.22 $
 * @author mosir
 */

class MlselectionController extends \common\controllers\BaseMallController
{
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['store_id','pid']);
		
		if(!in_array($post->type, array('region', 'gcategory'))) {
			return Message::warning(Language::get('invalid type'));
		}
		
		$model = new \frontend\models\MlselectionForm();
		$list = $model->formData($post);
		return Message::result($list);
    }
}