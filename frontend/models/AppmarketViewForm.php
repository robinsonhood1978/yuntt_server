<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\AppmarketModel;
use common\models\ApprenewalModel;

use common\library\Language;

/**
 * @Id AppmarketViewForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketViewForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		$query = AppmarketModel::find();
		
		if($post->id) {
			$query->where(['aid' => $post->id]);
		} elseif($post->appid) {
			$query->where(['appid' => $post->appid]);
		} else {
			$this->errors = Language::get('app_not_existed');
			return false;	
		}
		
		if(!($appmarket = $query->asArray()->one())) {
			$this->errors = Language::get('app_not_existed');
			return false;
		}
		
		if(!$appmarket['logo']){
			$appmarket['logo'] = Yii::$app->params['default_goods_image'];
		}
		
		if(ApprenewalModel::checkIsRenewal($appmarket['appid'], Yii::$app->user->id)){
			$appmarket['checkIsRenewal'] = true;
		}
		
		return $appmarket;
	}
}
