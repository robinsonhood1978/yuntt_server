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

use common\models\WeixinMenuModel;
use common\models\WeixinReplyModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id WeixinMenuForm.php 2018.8.27 $
 * @author mosir
 */
class WeixinMenuForm extends Model
{
	public $id = 0;
	public $userid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->name)) {
			$this->errors = Language::get('menuname_empty');
			return false;
		}
		if(!WeixinMenuModel::unique($post->name, $post->parent_id, $this->id, $this->userid)) {
			$this->errors = Language::get('menuname_exist');
			return false;
		}
		
		$model = new WeixinMenuModel();
		if(!$model->checkName($post->name, $post->parent_id, $this->id, $this->userid)) {
			$this->errors = $model->errors;
			return false;
		}
		
		if(!in_array($post->type, ['view','click'])) {
			$this->errors = Language::get('select_menu_type');
			return false;
		}
		if($post->type == 'view' && empty($post->link)) {
			$this->errors = Language::get('link_not_empty');
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->id || !($model = WeixinMenuModel::findOne($this->id))) {
			$model = new WeixinMenuModel();
		}
		
		$fields = ['name', 'parent_id', 'sort_order', 'type'];
		foreach($post as $key => $val) {
			if(in_array($key, $fields)) $model->$key = $val;
		}
		$model->userid = 0;
		$model->add_time = Timezone::gmtime();
		if($post->type == 'view') {
			$model->link = $post->link;
		} else $model->link = '';
		
		if($post->type == 'click') 
		{
			if($post->reply_id && (!$this->id || ($this->id && ($post->reply_id != $model->reply_id)))) {
				$model->reply_id = $post->reply_id;
			}
			else
			{
				if(!$model->reply_id || !($query = WeixinReplyModel::findOne($model->reply_id))) {
					$query = new WeixinReplyModel();
				}
				$query->userid = 0;
				$query->type = 1;
				$query->action = 'menu';
				$query->title = $post->reply_title;
				$query->link = $post->reply_link;
				$query->content = $post->reply_content;
				$query->add_time = Timezone::gmtime();
				
				if(isset($post->fileVal) && ($image = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_WEIXIN, 0, $post->fileVal)) !== false) {
					$query->image = $image;
				}
				if(!$query->save()) {
					$this->errors = $query->errors;
					return false;
				}
				$model->reply_id = $query->reply_id;
			}
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
