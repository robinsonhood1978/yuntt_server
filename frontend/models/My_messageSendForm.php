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
use common\models\MessageModel;
use common\models\FriendModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id My_messageSendForm.php 2018.10.4 $
 * @author mosir
 */
class My_messageSendForm extends Model
{
	public $errors = null;
	
	public function valid($post) 
	{
		if(empty($post->to_username) || !($users = UserModel::find()->select('userid')->where(['in', 'username', explode(',', $post->to_username)])->column())) {
			$this->errors = Language::get('no_to_username');
			return false;
		}
		if(in_array(Yii::$app->user->id, $users)) {
			$this->errors = Language::get('cannot_sent_to_myself');
			return false;
		}
		if(empty($post->content)) {
			$this->errors = Language::get('message_content_empty');
			return false;
		}
		
		return true;
	}
	
	public function send($post = null, $valid = true, $parent_id = 0)
    {
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$parent_id = intval($parent_id);
		foreach(explode(',', $post->to_username) as $username)
		{
			if(($query = UserModel::find()->select('userid')->where(['username' => $username])->one())) 
			{
				$model = new MessageModel();
				$model->from_id = Yii::$app->user->id;
				$model->to_id = $query->userid;
				$model->title = $post->title ? $post->title : '';
				$model->content = $post->content ? $post->content : '';
				$model->parent_id = $parent_id;
				$model->add_time  = Timezone::gmtime();
				$model->status = 3;  // 双方未删除
				$model->last_update = Timezone::gmtime();
				$model->new = $parent_id ? 0 : 1;
				if(!$model->save()) {
					$this->errors = $model->errors;
					return false;
				}
					
				if($parent_id > 0) {
					if(MessageModel::find()->select('from_id')->where(['msg_id' => $parent_id])->scalar() == Yii::$app->user->id) {
						MessageModel::updateAll(['new' => 1], ['msg_id' => $parent_id]);
					} else MessageModel::updateAll(['new' => 2], ['msg_id' => $parent_id]);	
				}
			}
		}
		return true;
    }
	
	public function getFriends($limit = 100)
	{
		$friends = FriendModel::find()->alias('f')->select('f.friend_id,u.userid,u.portrait,u.username')->joinWith('userFriend u', false, 'INNER JOIN')->where(['f.userid' => Yii::$app->user->id])->orderBy(['add_time' => SORT_DESC])->limit($limit)->asArray()->all();
		
		foreach($friends as $key => $val) {
			empty($val['portrait']) && $friends[$key]['portrait'] = Yii::$app->params['default_user_portrait'];
		}
		return $friends;
	}
	
	public function getUsersFromId($post = null)
	{
		if($post->to_id && ($users = UserModel::find()->select('username')->where(['in', 'userid', explode(',', $post->to_id)])->column())) {
			return join(',', $users);
		}
		return '';
	}	
}
