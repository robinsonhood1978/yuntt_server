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

use common\models\RecommendModel;

use common\library\Language;

/**
 * @Id RecommendForm.php 2018.8.14 $
 * @author mosir
 */
class RecommendForm extends Model
{
	public $recom_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->recom_name)) {
			$this->errors = Language::get('recom_name_empty');
			return false;
		}
		// edit
		if($this->recom_id) {
			if(RecommendModel::find()->where(['and', ['recom_name' => $post->recom_name], ['!=', 'recom_id', $this->recom_id]])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		// add
		else 
		{
			if(RecommendModel::find()->where(['recom_name' => $post->recom_name])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = RecommendModel::findOne($this->recom_id))) {
			$model = new RecommendModel();
		}
		$model->recom_id = $this->recom_id;
		$model->recom_name = $post->recom_name;
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		return true;
	}
}
