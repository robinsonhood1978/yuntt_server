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

use common\models\CashcardModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id CashcardForm.php 2018.8.20 $
 * @author mosir
 */
class CashcardForm extends Model
{
	public $id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->name)) {
			$this->errors = Language::get('name_empty');
			return false;
		}
		if($post->money > 10000 || $post->money <= 0) {
			$this->errors = Language::get('money_error');
			return false;
		}
		
		if($post->password && (strlen($post->password) < 6) || strlen($post->password) > 30) {
			$this->errors = Language::get('password_len_error');
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->id || !($model = CashcardModel::findOne($this->id))) {
			$model = new CashcardModel();
		}

		// 如果充值卡已被激活，则不允许修改
		if($model->id && $model->active_time > 0) {
			$this->errors = Language::get('actived_disallow');
			return false;
		}
	
		// 允许编辑的字段（编辑模式下，非仅行内编辑）
		foreach(['name', 'money', 'password', 'expire_time', 'printed'] as $key => $value) {

			if(isset($post->$value)) {
				if($value == 'expire_time') {
					$post->$value = $post->$value ? Timezone::gmstr2time_end($post->expire_time) : 0;
				}
				$model->$value = $post->$value;
			}
		}

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		return $model;
	}
	
	/**
	 * 创建充值卡
	 */
	public function create($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if($post->quantity <= 0 || $post->quantity > 1000) {
			$this->errors = Language::get('quantity_limit');
			return false;
		}
		
		for($i = 1; $i <= $post->quantity; $i++) {
			$model = new CashcardModel();
			$model->name = $post->name;
			$model->cardNo = CashcardModel::genCardNo();
			$model->money = $post->money;
			$model->password = $post->password ? $post->password : mt_rand(100000,999999);
			$model->expire_time = $post->expire_time ? Timezone::gmstr2time_end($post->expire_time) : 0;
			$model->active_time = 0;
			$model->useId = 0;
			$model->add_time = Timezone::gmtime();
			$model->save();
		}	
		return true;
	}
}
