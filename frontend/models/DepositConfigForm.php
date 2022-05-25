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

use common\models\DepositAccountModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id DepositConfigForm.php 2018.9.27 $
 * @author mosir
 */
class DepositConfigForm extends Model
{
	public $errors = null;
	
	public function valid($post = null)
	{
		if(!in_array(strtoupper($post->pay_status), array('ON','OFF'))) {
			$this->errors = Language::get('illegal_param');
			return false;
		}

		if(empty($post->password)) {
			$this->errors = Language::get('password_empty');
			return false;
		}
			
		if($post->password != $post->confirmPassword) {
			$this->errors = Language::get('password_confirm_error');
			return false;
		}
			
		// if($post->codeType == 'email')
		// {
		// 	if((Yii::$app->session->get('email_code') != md5(Yii::$app->user->identity->email.$post->code)) || (Yii::$app->session->get('last_send_time_email_code') + 120 < Timezone::gmtime())) {
		// 		$this->errors = Language::get('email_code_check_failed');
		// 		return false;
		// 	}		
		// }
		// // elseif($post->codeType == 'phone')
		// else
		// {
		// 	if((Yii::$app->session->get('phone_code') != md5(Yii::$app->user->identity->phone_mob.$post->code)) || (Yii::$app->session->get('last_send_time_phone_code') + 120 < Timezone::gmtime())) {
		// 		$this->errors = Language::get('phone_code_check_failed');
		// 		return false;
		// 	}
		// }
		
		return true;
	}
	
	/**
	 * 兼容API接口
	 */
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = DepositAccountModel::find()->where(['userid' => Yii::$app->user->id])->one())) {
			$this->errors = Language::get('account_empty');
			return false;
		}
		
		if($post->password) {
			$model->password = md5($post->password);
		}
		if($post->real_name) {
			$model->real_name = $post->real_name;
		}
		if($post->pay_status) {
			$model->pay_status = strtoupper($post->pay_status);
		}
		$model->last_update = Timezone::gmtime();
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
