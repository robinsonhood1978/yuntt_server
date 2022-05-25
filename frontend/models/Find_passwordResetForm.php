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

use common\models\UserModel;

use common\library\Language;

/**
 * @Id Find_passwordResetForm.php 2018.10.20 $
 * @author mosir
 */
class Find_passwordResetForm extends Model
{
	public $errors = null;
	
	public function valid($post = null)
	{
		if (empty($post->password) || empty($post->confirmPassword))
 		{
			$this->errors = Language::get('password_required');
    		return false;
 		}
		if(strlen($post->password) < 6 || strlen($post->password) > 20) {
			$this->errors = Language::get('password_length_error');
   			return false;
		}
    	if ($post->password != $post->confirmPassword)
  		{
			$this->errors = Language::get('password_not_equal');
     		return false;
      	}
		
		return true;
	}
	
	public function save($post, $get, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(empty($get->key) || !($user = UserModel::find()->where(['activation' => $get->key])->one())) {
			$this->errors = Language::get('request_error');
			return false;
		}

		$session = Yii::$app->session;
		$key = isset($session['email_code']) ? $session['email_code'] : (isset($session['phone_code']) ? $session['phone_code'] : '');
		if($key != $get->key) {
			$this->errors = Language::get('session_expire');
			return false;
		}
			
		// 全部验证通过，修改密码
        $user->setPassword($post->password);
		$user->activation = '';
        $user->removePasswordResetToken();
		if(!$user->save()) {
			$this->errors = $user->errors;
			return false;
		}
		Yii::$app->session->remove('email_code');
		Yii::$app->session->remove('last_send_time_email_code');
		Yii::$app->session->remove('phone_code');
		Yii::$app->session->remove('last_send_time_phone_code');
		
		return $user;
	}
}