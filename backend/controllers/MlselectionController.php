<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id MlselectionController.php 2018.4.22 $
 * @author mosir
 */

class MlselectionController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['store_id','pid']);
		
		if(!in_array($post->type, array('region', 'gcategory'))) {
			return Message::warning(Language::get('invalid type'));
		}
		
		$model = new \backend\models\MlselectionForm();
		return Message::result($model->formData($post));
    }
}