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

use common\models\StoreModel;
use common\library\Language;

/**
 * @Id My_storeForm.php 2018.10.19 $
 * @author mosir
 */
class My_storeForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function valid($post = null)
	{
		if(!$this->store_id) {
			$this->errors = Language::get('no_such_store');
			return false;
		}
		if(empty($post->store_name)) {
			$this->errors = Language::get('store_name_empty');
			return false;
		}
			
		if(StoreModel::find()->where(['store_name' => $post->store_name])->andWhere(['<>', 'store_id', $this->store_id])->exists()) {
			$this->errors = Language::get('name_exist');
			return false;
		}
		
		if(!$post->region_id || empty($post->region_name) || empty($post->address)) {
			$this->errors = Language::get('region_empty');
			return false;
		}
		
		return true;
	}
	
	public function save($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = StoreModel::findOne($this->store_id))) {
			$this->errors = Language::get('no_such_store');
			return false;
		}
		
		$model->store_name = $post->store_name;
		$model->region_id = $post->region_id;
		$model->region_name = $post->region_name;
		$model->address = $post->address;
		$model->tel = $post->tel;
		$model->im_qq = $post->im_qq;
		
		if(isset($post->store_logo) && $post->store_logo) {
			$model->store_logo = $post->store_logo;
		}
		if(isset($post->store_banner) && $post->store_banner) {
			$model->store_banner = $post->store_banner;
		}
		
		// for PC
		if(isset($post->description)) $model->description = $post->description;
		
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('edit_fail');
			return false;
		}
		return true;		
	}
}
