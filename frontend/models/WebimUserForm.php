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
 * @Id WebimUserForm.php 2018.10.20 $
 * @author mosir
 */
class WebimUserForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		// 如果不传toid 说明是读取当前访客的信息，如果传toid，说明是获取客服的信息
		$userid = $post->toid ? $post->toid : Yii::$app->user->id;
		if(!UserModel::find()->where(['userid' => $userid])->exists()) {
			$this->errors = Language::get('no_such_user');
			return false;
		}
		
		list($avatar, $username) = UserModel::getAvatarById($userid);
		$result = ['id' => $userid, 'username' => $username, 'avatar' => $avatar, 'type' => 'friend'];
		
		if(!$post->toid) {
			$result['groupid'] = 1;
			$result['sign']    = '';
		}
		else
		{
			$result['name'] = $username;
		}
		
		return $result;
	}
}
