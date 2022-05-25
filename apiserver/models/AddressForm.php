<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 

use common\models\AddressModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use yii\helpers\ArrayHelper;

/**
 * @Id AddressForm.php 2018.10.23 $
 * @author yxyc
 */
class AddressForm extends Model
{
	public $addr_id = 0;
	public $errors = null;
	
	/** 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		// $fields = ['consignee', 'phone_mob', 'region_id', 'address'];
		$fields = ['consignee', 'phone_mob', 'address'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}
		
		// 例外判断
		if(isset($post->phone_mob) && (Basewind::isPhone($post->phone_mob) == false)) {
			$this->errors = Language::get('phone_mob_invalid');
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !($this->valid($post))) {
			return false;
		}
		
		if(!$this->addr_id || !($model = AddressModel::find()->where(['addr_id' => $this->addr_id, 'userid' => Yii::$app->user->id])->one())) {
			$model = new AddressModel();
		}
		
		$model->userid = Yii::$app->user->id;
		
		if(isset($post->consignee)) $model->consignee = $post->consignee;
		if(isset($post->region_id)) {
			$model->region_id = $post->region_id;
			$model->region_name = implode(' ', RegionModel::getArrayRegion($post->region_id));
		}
		if(isset($post->address)) $model->address = $post->address;
		if(isset($post->zipcode)) $model->zipcode = $post->zipcode;
		if(isset($post->phone_tel)) $model->phone_tel = $post->phone_tel;
		if(isset($post->phone_mob)) $model->phone_mob = $post->phone_mob;
		if(isset($post->defaddr)) $model->defaddr = $post->defaddr === true ? 1 : 0;

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		if($post->defaddr) {
			AddressModel::updateAll(['defaddr' => 0], ['and', ['userid' => Yii::$app->user->id], ['!=', 'addr_id', $model->addr_id]]);
		}
		
		return ArrayHelper::toArray($model);
	}
	
	public function exists($post)
	{
		if(!AddressModel::find()->where(['addr_id' => $this->addr_id, 'userid' => Yii::$app->user->id])->exists()) {
			$this->errors = Language::get('address_invalid');
			return false;
		}
		return true;
	}
	
	/**
	 * 如果是新增，则一律判断
	 * 如果是编辑，则设置值了才判断
	 */
	private function isempty($post, $fields)
	{
		if($this->exists($post)) {
			if(isset($post->$fields)) {
				return empty($post->$fields);
			}
			return false;
		}
		return empty($post->$fields);
	}
}
