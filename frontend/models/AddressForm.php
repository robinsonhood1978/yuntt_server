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

use common\models\AddressModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;

/**
 * @Id AddressForm.php 2018.4.23 $
 * @author mosir
 */
class AddressForm extends Model
{
	public $addr_id = 0;
	public $errors = null;

	public function valid($post)
	{
		if(empty($post->consignee)) {
			$this->errors = Language::get('consignee_required');
			return false;
		}
		if(Basewind::isPhone($post->phone_mob) == false) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}
		if(!$post->region_id) {
			$this->errors = Language::get('region_required');
			return false;
		}
		if(empty($post->address)) {
			$this->errors = Language::get('address_required');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->addr_id || !($model = AddressModel::find()->where(['addr_id' => $this->addr_id, 'userid' => Yii::$app->user->id])->one())) {
			$model = new AddressModel();
		}
		
		$model->userid 		= Yii::$app->user->id;
		$model->consignee 	= $post->consignee;
		$model->region_id 	= $post->region_id;
		$model->region_name = implode(' ', RegionModel::getArrayRegion($post->region_id));
		$model->address 	= $post->address;
		$model->zipcode 	= $post->zipcode ? $post->zipcode : '';
		$model->phone_tel	= $post->phone_tel ? $post->phone_tel : '';
		$model->phone_mob 	= $post->phone_mob;
		$model->defaddr 	= $post->defaddr ? $post->defaddr : 0;
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		if($post->defaddr) {
			AddressModel::updateAll(['defaddr' => 0], ['and', ['userid' => Yii::$app->user->id], ['!=', 'addr_id', $model->addr_id]]);
		}
		AddressModel::setIndexAddress();
		return true;
		
	}
}
