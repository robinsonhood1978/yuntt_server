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

use common\models\DepositSettingModel;

use common\library\Language;

/**
 * @Id DepositSettingForm.php 2018.8.6 $
 * @author mosir
 */
class DepositSettingForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
    public function valid($post)
	{
		if(!$this->isNumeric($post)) {
			$this->errors = Language::get('number_error');
			return false;
		}

		return true;
	}

	/**
	 * 这里的保存，有可能提交一个字段，也有可能提交几个字段，有可能是用户配置，也有可能是系统配置
	 * 在新增情况下，如果提交的是用户配置，可将缺省字段设置为-1，以确保没有提交的字段取值系统配置，以达到用户配置了正常值，取用户配置，没有正确配置取系统配置。
	 */
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = DepositSettingModel::find()->where(['userid' => $this->userid])->one())) {
			$model = new DepositSettingModel();
		}
		$model->userid = $this->userid;
		
		$fields = $this->getFields();
		foreach($fields as $field) {
			if(isset($post->$field)) {
				$model->$field = floatval($post->$field);
			}

			// 在新增情况下，如果是用户配置，将缺省字段设置值为-1,让该字段取值继承系统配置
			elseif($this->userid && !$model->setting_id) {
				$model->$field = -1;
			}
		}

		return $model->save() ? true : false;
	}

	private function isNumeric($post)
	{
		$fields = $this->getFields();
		foreach($fields as $field) {
			if(!isset($post->$field)) {
				continue;
			}

			if(!is_numeric($post->$field) || $post->$field < 0 || $post->$field >= 1) {
				return false;
			}
		}

		return true;
	}

	private function getFields() {
		return ['trade_rate', 'transfer_rate', 'regive_rate', 'guider_rate'];
	}
}
