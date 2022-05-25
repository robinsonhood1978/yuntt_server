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
use yii\helpers\ArrayHelper;

use common\models\SgradeModel;

use common\library\Language;

/**
 * @Id SgradeForm.php 2018.8.17 $
 * @author mosir
 */
class SgradeForm extends Model
{
	public $grade_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->grade_name)) {
			$this->errors = Language::get('sgrade_empty');
			return false;
		}
		
		// edit
		if($this->grade_id) {
			if(SgradeModel::find()->where(['grade_name' => $post->grade_name])->andWhere(['!=', 'grade_id', $this->grade_id])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		
		// add
		else {
			if(SgradeModel::find()->where(['grade_name' => $post->grade_name])->exists()) {
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

		if(!$this->grade_id || !($model = SgradeModel::findOne($this->grade_id))) {
			$model = new SgradeModel();
		}
		
		$fields = ['grade_name', 'goods_limit', 'space_limit', 'charge', 'need_confirm', 'description', 'sort_order'];
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
