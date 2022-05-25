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

use common\models\WeixinReplyModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id WeixinReplyForm.php 2018.8.28 $
 * @author mosir
 */
class WeixinReplyForm extends Model
{
	public $reply_id = 0;
	public $userid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(!in_array($post->action, ['beadded','autoreply','smartreply'])) {
			$this->errors = Language::get('action_invalid');
			return false;
		}

		if($post->action == 'smartreply') {
			if(!isset($post->rule_name) || empty($post->rule_name)) {
				$this->errors = Language::get('rule_name_empty');
				return false;
			}
			if(!isset($post->keywords) || empty($post->keywords)) {
				$this->errors = Language::get('keywords_empty');
				return false;
			}
		}
		
		if($post->type > 0) {
			if(!isset($post->title) || empty($post->title)) {
				$this->errors = Language::get('title_empty');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->reply_id || !($model = WeixinReplyModel::findOne($this->reply_id))) {
			if(in_array($post->action, ['beadded','autoreply']) && WeixinReplyModel::find()->where(['userid' => $this->userid, 'action' => $post->action])->exists()) {
				$this->errors = Language::get($post->action.'_add_already');
				return false;
			}
			$model = new WeixinReplyModel();
		}
		
		$fields = ['type', 'action', 'rule_name', 'keywords', 'description'];
		foreach($post as $key => $val) {
			if(in_array($key, $fields)) $model->$key = $val;
		}
		$model->userid = $this->userid;
		$model->add_time = Timezone::gmtime();
		
		if($post->type > 0) 
		{
			$model->title = addslashes($post->title);
			$model->link = $post->link;
			if(isset($post->fileVal) && ($image = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_WEIXIN, 0)) !== false) {
				$model->image = $image;
			}
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
