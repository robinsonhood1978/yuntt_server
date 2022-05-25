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

use common\models\MessageModel;
use common\library\Language;

/**
 * @Id My_messageDeleteForm.php 2018.10.5 $
 * @author mosir
 */
class My_messageDeleteForm extends Model
{
	public $errors = null;
	
	public function valid($post)
	{
        if (!$post->msg_id) {
			$this->errors = Language::get('no_such_message');
            return false;
        }
	
		return true;		
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$messages = MessageModel::find()->select('msg_id,from_id,to_id,parent_id,status')->where(['in', 'msg_id', explode(',', $post->msg_id)])->all();
		if(!$messages) {
			$this->errors = Language::get('no_such_message');
            return false;
		}
		
		foreach($messages as $message) 
		{
			if($message->from_id == 0 && ($message->to_id == Yii::$app->user->id)) {
				// 系统发给自己的信息，可以删除
				MessageModel::deleteAll(['or', ['msg_id' => $message->msg_id], ['parent_id' => $message->msg_id]]);
			}
			
			// 收件箱，用户发给自己的信息，双方同意才能被删除
			// status = 1  发件方删除
			// status = 2  收件方删除
			// status = 3  双方都没删除
			elseif($message->to_id == Yii::$app->user->id) {
				if(in_array($message->status, [2,3])) {
					MessageModel::updateAll(['status' => 2], ['msg_id' => $message->msg_id]);
				} else {
					MessageModel::deleteAll(['msg_id' => $message->msg_id]); 
					MessageModel::deleteAll(['or', ['msg_id' => $message->msg_id], ['parent_id' => $message->msg_id]]);
				}
			}
			elseif($message->from_id == Yii::$app->user->id) {
				if(in_array($message->status, [1,3])) {
					MessageModel::updateAll(['status' => 1], ['msg_id' => $message->msg_id]);
				} else {
					MessageModel::deleteAll(['msg_id' => $message->msg_id]); 
					MessageModel::deleteAll(['or', ['msg_id' => $message->msg_id], ['parent_id' => $message->msg_id]]);
				}
			}
		}
		
		return true;
	}
}
