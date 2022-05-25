<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\FlagstoreModel;
use common\models\UserModel;

use common\library\Language;

/**
 * @Id FlagstoreForm.php 2018.8.18 $
 * @author mosir
 */
class FlagstoreForm extends Model
{
	public $fid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		// edit
		if($this->fid) {
			if($post->brand_id && FlagstoreModel::find()->where(['brand_id' => $post->brand_id])->andWhere(['!=', 'fid', $this->fid])->exists()) {
				$this->errors = Language::get('brand_exist');
				return false;
			}
			if($post->keyword && FlagstoreModel::find()->where(['keyword' => $post->keyword])->andWhere(['!=', 'fid', $this->fid])->exists()) {
				$this->errors = Language::get('keyword_exist');
				return false;
			}
			if($post->cate_id && FlagstoreModel::find()->where(['cate_id' => $post->cate_id])->andWhere(['!=', 'fid', $this->fid])->exists()) {
				$this->errors = Language::get('gcategory_exist');
				return false;
			}
		}
		
		// add
		else {
			if($post->brand_id && FlagstoreModel::find()->where(['brand_id' => $post->brand_id])->exists()) {
				$this->errors = Language::get('brand_exist');
				return false;
			}
			if($post->keyword && FlagstoreModel::find()->where(['keyword' => $post->keyword])->exists()) {
				$this->errors = Language::get('keyword_exist');
				return false;
			}
			if($post->cate_id && FlagstoreModel::find()->where(['cate_id' => $post->cate_id])->exists()) {
				$this->errors = Language::get('gcategory_exist');
				return false;
			}
			if(empty($post->username) || !UserModel::find()->alias('u')->select('s.store_id')->joinWith('store s', false)->where(['username' => $post->username])->scalar()) {
				$this->errors = Language::get('user_no_store');
				return false;
			}
		}
		
		if(!$post->brand_id && !$post->cate_id && empty($post->keyword)) {
			$this->errors = Language::get('relate_empty');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->fid || !($model = FlagstoreModel::findOne($this->fid))) {
			$model = new FlagstoreModel();
			$model->store_id = UserModel::find()->alias('u')->select('s.store_id')->joinWith('store s', false)->where(['username' => $post->username])->scalar();
		}
		
		$fields = ['brand_id', 'cate_id', 'keyword', 'description', 'status', 'sort_order'];
		foreach($post as $key => $val) {
			if(in_array($key, $fields)) $model->$key = $val;
		}
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return $model;
	}
}
