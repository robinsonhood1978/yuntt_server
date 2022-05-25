<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 

use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;

/**
 * @Id UserForm.php 2018.10.25 $
 * @author yxyc
 */
class UserForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
	/* 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		$fields = ['username', 'password', 'phone_mob'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}
		
		// 唯一性判断
		if($post->username) {
			if(Basewind::checkUser($post->username, Yii::$app->user->id)) {
				$this->errors = Language::get('username_existed');
				return false;
			}
		}
		
		// 唯一性判断
		if($post->phone_mob) {
			if(!Basewind::isPhone($post->phone_mob)) {
				$this->errors = Language::get('phone_mob_invalid');
				return false;
			}
			if(!Basewind::checkPhone($post->phone_mob, Yii::$app->user->id)) {
				$this->errors = Language::get('phone_mob_existed');
				return false;
			}
		}

		// 唯一性判断
		if($post->email) {
			if(!Basewind::isEmail($post->email)) {
				$this->errors = Language::get('email_invalid');
				return false;
			}
			if(!Basewind::checkEmail($post->email, Yii::$app->user->id)) {
				$this->errors = Language::get('email_existed');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !($this->valid($post))) {
			return false;
		}
		
		if($this->userid) {
			$model = UserModel::find()->where(['userid' => $this->userid])->one();
		} 
		else {
			$model = new UserModel();
			$model->username = $post->username;

			// 只有新增时才给提交手机号，修改手机号需要短信验证
			if(isset($post->phone_mob)) $model->phone_mob = $post->phone_mob;
		}
	
		if(isset($post->email)) $model->email = $post->email;
		if(isset($post->nickname)) $model->nickname = $post->nickname;
		if(isset($post->real_name)) $model->real_name = $post->real_name;
		if(isset($post->gender)) $model->gender = $post->gender;
		if(isset($post->birthday)) $model->birthday = $post->birthday;
		if(isset($post->im_qq)) $model->im_qq = $post->im_qq;
		if(isset($post->portrait)) $model->portrait = $post->portrait;
		if(isset($post->password)) $model->setPassword($post->password);
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		$this->userid = $model->userid;
		
		return true;	
	}
	
	public function exists($post)
	{
		if(!UserModel::find()->where(['userid' => $this->userid])->exists()) {
			$this->errors = Language::get('user_invalid');
			return false;
		}
		return true;
	}
	
	/*
	 * 如果是新增，则一律判断
	 * 如果是编辑，则设置值了才判断
	 */
	public function isempty($post, $fields)
	{
		if($this->userid) {
			if(isset($post->$fields)) {
				return empty($post->$fields);
			}
			return false;
		}
		return empty($post->$fields);
	}
}
