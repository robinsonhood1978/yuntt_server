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

use common\models\IntegralSettingModel;

use common\library\Language;

/**
 * @Id IntegralSettingForm.php 2018.8.6 $
 * @author mosir
 */
class IntegralSettingForm extends Model
{
	public $errors = null;
	
    public function valid($post)
	{
		if(!$this->isNumeric($post, ['rate', 'register', 'signin', 'openshop'])) {
			$this->errors = Language::get('number_error');
			return false;
		}
		if(($post->rate < 0) || ($post->rate > 1) || ($post->register < 0) || ($post->signin < 0) || ($post->openshop < 0)) {
			$this->errors = Language::get('number_error');
			return false;
		}
		
		if($post->buygoods) {
			foreach($post->buygoods as $key => $val) {
				if(!is_numeric($val) || $val < 0 || $val > 1) {
					$this->errors = Language::get('number_error');
					return false;
				}
			}
		}
		return true;
	}
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = IntegralSettingModel::find()->one())) {
			$model = new IntegralSettingModel();
		}
		$model->rate = floatval($post->rate);
		$model->register = floatval($post->register);
		$model->signin = floatval($post->signin);
		$model->openshop = floatval($post->openshop);
		$model->buygoods = serialize(ArrayHelper::toArray($post->buygoods));
		$model->enabled = intval($post->enabled);
		return $model->save() ? true : false;
	}
	private function isNumeric($post, $fields = array())
	{
		foreach($fields as $field) {
			if(isset($post->$field) && !empty($post->$field) && !is_numeric($post->$field)) {
				return false;
			}
		}
		return true;
	}
}
