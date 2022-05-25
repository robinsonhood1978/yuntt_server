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
use common\models\MsgModel;
use common\models\MsgLogModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Plugin;

/**
 * @Id MsgForm.php 2018.8.23 $
 * @author mosir
 */
class MsgForm extends Model
{
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->username) || !UserModel::find()->where(['username' => $post->username])->exists()) {
			$this->errors = Language::get('no_such_user');
			return false;
		}

		if($post->num <= 0) {
			$this->errors = Language::get('num_invalid');
			return false;
		}

		// 减少短信数
		$query = MsgModel::find()->alias('msg')->select('msg.num,msg.userid')->joinWith('user u', false)->where(['username' => $post->username])->one();
		if($post->direction =='reduce' && ($query->num < $post->num)) {
			$this->errors = sprintf(Language::get('err_num_smaller'), $query->num, $post->num);
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		$query = UserModel::find()->select('userid')->where(['username' => $post->username])->one();
		if(!($model = MsgModel::find()->where(['userid' => $query->userid])->one())) {
			$model = new MsgModel();
			$model->userid = $query->userid;
			$model->save();
		}
		if(!MsgModel::updateAllCounters(['num' => ($post->direction == 'add') ? $post->num : -$post->num], ['userid' => $model->userid])) {
			return false;
		}

		$query = new MsgLogModel();
		$query->code = Plugin::getInstance('sms')->autoBuild()->getCode();
		$query->userid = $model->userid;
		$query->quantity = $post->direction == 'add' ? $post->num : -$post->num;
		$query->status = 1;
		$query->type = 1; // 记录类型：0 代表发送记录 1 代表充值记录
		$query->message = $post->content;
		$query->add_time = Timezone::gmtime();
		
		return $query->save() ? true : false;
	}
}
