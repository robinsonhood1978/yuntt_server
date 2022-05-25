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
use yii\helpers\Url;

use common\models\GoodsQaModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Language;

/**
 * @Id My_qaReplyForm.php 2018.10.19 $
 * @author mosir
 */
class My_qaReplyForm extends Model
{
	public $store_id = null;
	public $errors = null;
	
	public function valid($post = null)
	{
		if(empty($post->content)) {
			$this->errors = Language::get('content_not_null');
			return false;
		}
		return true;		
	}
	
	public function save($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!$post->id || !($model = GoodsQaModel::find()->select('ques_id,item_id,item_name,type,email')->where(['ques_id' => $post->id, 'store_id' => $this->store_id])->one())) {
			$this->errors = Language::get('reply_failed');
			return false;
		}
		
		$model->reply_content = $post->content;
		$model->time_reply = Timezone::gmtime();
		$model->if_new = 1;
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('reply_failed');
			return false;
		}
	
		// 发送给买家 咨询已回复的通知
		$mailer = Basewind::getMailer('tobuyer_question_replied', ['item_name' => $model->item_name, 'type' => Language::get($model->type), 'url' => Url::toRoute(['goods/qa', 'id' => $model->item_id], true), 'user' => UserModel::find()->select('username')->where(['userid' => $model->userid])->asArray()->one()]);
		if($mailer && ($toEmail = $model->email)) {
			$mailer->setTo($toEmail)->send();
		}
		
		return true;
	}
}
