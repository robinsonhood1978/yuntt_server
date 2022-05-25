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

use common\models\MsgTemplateModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id MsgTemplateForm.php 2018.8.23 $
 * @author mosir
 */
class MsgTemplateForm extends Model
{
	public $id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->code)) {
			$this->errors = Language::get('no_such_smsplat');
			return false;
		}

		if(empty($post->scene)) {
			$this->errors = Language::get('scene_invalid');
			return false;
		}
		// 阿里大鱼平台，签名必填
		if(empty($post->signName) && in_array($post->code, ['alidayu'])) {
			$this->errors = Language::get('sign_name_empty');
			return false;
		}
		// 阿里大鱼平台，短信模板ID必填
		if(empty($post->templateId) && in_array($post->code, ['alidayu'])) {
			$this->errors = Language::get('template_id_empty');
			return false;
		}
		if(empty($post->content)){
			$this->errors = Language::get('template_content_empty');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->id || !($model = MsgTemplateModel::findOne($this->id))) {
			if(!($model = MsgTemplateModel::find()->where(['code' => $post->code, 'scene' => $post->scene])->one())) {
				$model = new MsgTemplateModel();
				$model->add_time = Timezone::gmtime();
			}
		}

		$model->code = $post->code;
		$model->scene = $post->scene;
		$model->signName = $post->signName;
		$model->templateId = $post->templateId;
		$model->content = $post->content;
		
		if(!$model->save()) {
			$this->errros = $model->errors;
			return false;
		}
		return true;
	}
}
