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

use common\library\Language;

/**
 * @Id My_messageViewForm.php 2018.10.5 $
 * @author mosir
 */
class My_messageViewForm extends Model
{
	public $errors = null;
	
	public function send($post = null, $get = null, $message = array())
    {
		if($message['to_id'] == Yii::$app->user->id) {
			$to_id = $message['from_id'];
		} elseif($message['from_id'] == Yii::$app->user->id) {
			$to_id = $message['to_id'];
		} else $to_id = 0;
				
		if(!$to_id) {
			$this->errors = Language::get('canot_reply_system_message');
			return false;
		}
		
		if ($to_id == Yii::$app->user->id) {
			$this->errors = Language::get('cannot_sent_to_myself');
			return false;
		}
		
		if(!($to_username = UserModel::find()->select('username')->where(['userid' => $to_id])->scalar())) {
			$this->errors = Language::get('no_such_user');
			return false;
		} else $post->to_username = $to_username;
		
		$model = new \frontend\models\My_messageSendForm();
   		if(!$model->send($post, false, $get->msg_id)) {
			$this->errors = $model->errors;
			return false;
		}
		
		return true;
    }
	
	public function formData($post = null, $reply = false)
    {
		$message = MessageModel::find()->where(['msg_id' => $post->msg_id, 'parent_id' => 0])->andWhere(['or', ['and', ['to_id' => Yii::$app->user->id], ['in', 'status', [1,3]]], ['and', ['from_id' => Yii::$app->user->id], ['in', 'status', [2,3]]]])->asArray()->one();
		
		if(!$message) {
			$this->errors = Language::get('no_such_message');
			return  false;
		}
		
		if($message['from_id'] == 0) {
			$message['username'] = Language::get('system_msg');
			$message['system'] = 1;
		}
		elseif(($user = UserModel::find()->select('username,portrait')->where(['userid' => $message['from_id']])->asArray()->one())) {
			empty($user['portrait']) && $user['portrait'] = Yii::$app->params['default_user_portrait'];
			$message = array_merge($message, $user);				
		}
			
		if(($message['from_id'] == Yii::$app->user->id && $message['new'] == 2) || ($message['to_id'] == Yii::$app->user->id && $message['new'] == 1)) {
			$message['new'] = 1;
		} else $message['new'] = 0;
			
		// 私人短消息
		if(in_array(Yii::$app->user->id, [$message['from_id'], $message['to_id']])) {
			$message['privatepm'] = 1;
				
			if($reply && ($replies = MessageModel::find()->where(['parent_id' => $post->msg_id])->asArray()->all())) {
				foreach ($replies as $key => $val) {
					$reply = UserModel::find()->select('username,portrait')->where(['userid' => $val['from_id']])->asArray()->one();
					if($reply) {
						empty($reply['portrait']) && $reply['portrait'] = Yii::$app->params['default_user_portrait'];
						$replies[$key] = array_merge($val, $reply);
					}
            	}
				$message['replies'] = $replies;
			}
		}
		return $message;
    }
}
