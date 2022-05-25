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
use common\models\DepositAccountModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id UserRegisterForm.php 2018.4.3 $
 * @author mosir
 * @desc User register request form
 */
class UserRegisterForm extends Model
{
	public $username;
	public $password;
	public $confirmPassword;
	public $email;
	public $phone_mob;
	public $agree;

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['username', 'password', 'confirmPassword', 'email', 'phone_mob'], 'trim'],
			['username', 'required', 'message' => Language::get('username_required')],
			['username', 'string', 'length' => [3, 25], 'message' => Language::get('username_length_error')],
			['username', 'unique', 'targetClass' => 'common\models\UserModel', 'message' => Language::get('username_existed')],

			['password', 'required', 'message' => Language::get('password_required')],
			['password', 'string', 'length' => [6, 20]],
			['password', 'compare', 'compareAttribute' => 'confirmPassword', 'message' => Language::get('inconsistent_password')],

			['phone_mob', 'required', 'message' => Language::get('phone_mob_required')],
			['phone_mob', 'isPhone'], // It can also be used regular
			['phone_mob', 'unique', 'targetClass' => 'common\models\UserModel', 'message' => Language::get('phone_mob_existed')],

			//['email', 'required'],
			['email', 'email'],
			['email', 'string', 'max' => 50],
			['email', 'unique', 'targetClass' => 'common\models\UserModel', 'message' => Language::get('email_exist')],

			['agree', 'required', 'message' => Language::get('agree_first')],
			['agree', 'integer'],
		];
	}

	/**
	 * Validates the password.
	 * This method serves as the inline validation for password.
	 *
	 * @param string $attribute the attribute currently being validated
	 * @param array $params the additional name-value pairs given in the rule
	 */
	public function validatePassword($attribute, $params)
	{
		if (!$this->hasErrors()) {
			$user = UserModel::find()->where(['userid' => Yii::$app->user->id])->one();
			if (!$user || !$user->validatePassword($this->password)) {
				$this->addError($attribute, Language::get('orig_pass_not_right'));
			}
		}
	}

	public function isPhone($attribute, $params)
	{
		if (!$this->hasErrors()) {
			if (Basewind::isPhone($this->phone_mob) == false) {
				$this->addError($attribute, Language::get('phone_mob_invalid'));
			}
		}
	}

	public function register($extra = array())
	{
		$user = new UserModel();
		$user->username = $this->username;
		$user->email = $this->email ? $this->email : '';
		$user->phone_mob = $this->phone_mob;
		$user->last_login = Timezone::gmtime();
		$user->logins = 1;
		$user->last_ip = Yii::$app->request->userIP;
		$user->setPassword($this->password);

		foreach ($extra as $key => $val) {
			$val && $user->$key = $val;
		}
		$user->generateAuthKey();
		if ($user->save()) {
			// 创建资金账户
			DepositAccountModel::createDepositAccount($user->userid);

			// 注册送积分
			IntegralModel::updateIntegral(['userid' => $user->userid, 'type' => 'register_has_integral', 'amount' => IntegralSettingModel::getSysSetting('register')]);
			return $user;
		}
		return null;
	}
}
