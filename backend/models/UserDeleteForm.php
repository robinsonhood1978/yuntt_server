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
use common\models\BindModel;
use common\models\IntegralModel;
use common\models\UserEnterModel;
use common\models\MsgModel;

/**
 * @Id UserDeleteForm.php 2018.8.18 $
 * @author mosir
 */
class UserDeleteForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
	
		foreach(explode(',', $this->userid) as $id) {
			if($model = UserModel::findOne($id)) {
				if($model->delete() === false) {
					$this->errors = $model->errors;
					return false;
				}
				// 删除用户权限
				UserPrivModel::deleteAll(['userid' => $id]);
				// 删除用户绑定
				BindModel::deleteAll(['userid' => $id]);
				// 删除用户积分
				IntegralModel::deleteAll(['userid' => $id]);
				// 删除用户访问记录
				UserEnterModel::deleteAll(['userid' => $id]);
				// 删除短信用户
				MsgModel::deleteAll(['userid' => $id, 'num' => 0]);
			}
		}
		return true;
	}
}
