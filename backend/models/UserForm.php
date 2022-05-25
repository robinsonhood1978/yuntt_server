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

use common\models\UserModel;
use common\models\UserPrivModel;
use common\models\UploadedFileModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id UserForm.php 2018.7.27 $
 * @author mosir
 */
class UserForm extends Model
{
	public $userid = 0;
	public $errors = null;

	public function valid($post)
	{
		// add
		if (!$this->userid) {
			if (strlen($post->username) < 3 || strlen($post->username) > 15) {
				$this->errors = Language::get('user_length_limit');
				return false;
			}

			if (!Basewind::checkUser($post->username)) {
				$this->errors = Language::get('name_exist');
				return false;
			}

			if (strlen($post->password) < 6 || strlen($post->password) > 20) {
				$this->errors = Language::get('password_length_error');
				return false;
			}
		}
		// edit
		else {
			if ($post->password && strlen($post->password) < 6 || strlen($post->password) > 20) {
				$this->errors = Language::get('password_length_error');
				return false;
			}
			if (UserPrivModel::isAdmin($this->userid) && (Yii::$app->user->id != $this->userid)) {
				$this->errors = Language::get('system_admin_edit');
				return false;
			}
		}

		if ($post->email) {
			if (!Basewind::isEmail($post->email)) {
				$this->errors = Language::get('email_error');
				return false;
			}
			if (!Basewind::checkEmail($post->email, $this->userid)) {
				$this->errors = Language::get('email_exists');
				return false;
			}
		}
		if (!Basewind::isPhone($post->phone_mob)) {
			$this->errors = Language::get('phone_mob_error');
			return false;
		}
		if (!Basewind::checkPhone($post->phone_mob, $this->userid)) {
			$this->errors = Language::get('phone_mob_existed');
			return false;
		}
		return true;
	}

	public function save($post, $valid = true)
	{
		if ($valid === true && !$this->valid($post)) {
			return false;
		}

		if (!$this->userid || !($model = UserModel::findOne($this->userid))) {
			$model = new UserModel();
			$model->username = $post->username;
		}
		$model->email = $post->email;
		$model->phone_mob = $post->phone_mob;

		if (!$this->userid || ($this->userid && $post->password)) {
			$model->setPassword($post->password);
		}
		$model->gender = $post->gender;
		$model->im_qq = $post->im_qq ? $post->im_qq : '';
		$model->real_name = $post->real_name ? $post->real_name : '';
		$model->locked = $post->locked;
		$model->generateAuthKey();

		if ($model->save()) {
			if (isset($post->fileVal) && ($portrait = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_PORTRAIT, $model->userid, $post->fileVal)) !== false) {
				$model->portrait = $portrait;
				$model->save();
			}

			// 注册送积分
			if (!$this->userid) {
				IntegralModel::updateIntegral(['userid' => $model->userid, 'type' => 'register_has_integral', 'amount' => IntegralSettingModel::getSysSetting('register')]);
			}

			return $model;
		}
		return null;
	}
}
