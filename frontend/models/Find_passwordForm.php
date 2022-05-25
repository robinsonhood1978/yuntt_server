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
use yii\captcha\CaptchaValidator;

use common\models\UserModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id Find_passwordForm.php 2018.10.20 $
 * @author mosir
 */
class Find_passwordForm extends Model
{
	public $errors = null;
	
	public function valid($post = null)
	{
		if (empty($post->username) || empty($post->captcha) || empty($post->codeType) || empty($post->code)) {
			$this->errors = Language::get('unsettled_required');
   			return false;
   		}
			
		$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
		if(!$captchaValidator->validate($post->captcha)) {
			$this->errors = Language::get('captcha_failed');
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$user = UserModel::find()->select('userid,email,phone_mob')->where(['username' => $post->username])->one();
   		if(!$user || empty($user->email) && empty($user->phone_mob)) {
			$this->errors = Language::get('no_such_user');
			return false;
		}

		if($post->codeType == 'email')
		{
			if((Yii::$app->session->get('email_code') != md5($user->email.$post->code)) || (Yii::$app->session->get('last_send_time_email_code') + 120 < Timezone::gmtime())) {
				$this->errors = Language::get('email_code_check_failed');
				return false;
			}		
		}
		elseif($post->codeType == 'phone')
		{
			if((Yii::$app->session->get('phone_code') != md5($user->phone_mob.$post->code)) || (Yii::$app->session->get('last_send_time_phone_code') + 120 < Timezone::gmtime())) {
				$this->errors = Language::get('phone_code_check_failed');
				return false;
			}
		} else {
			$this->errors = Language::get('request_exception');
			return false;
		}
		
		// 至此，验证通过
		$activation = ($post->codeType == 'email') ? Yii::$app->session->get('email_code') : Yii::$app->session->get('phone_code');	
		$user->activation = $activation;
		
		if(!$user->save()) {
			$this->errors = Language::get('activation_fail');
			return false;
		}
		
		return $user;
	}
}
