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
use yii\helpers\ArrayHelper;

use common\models\AppmarketModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id AppmarketForm.php 2018.8.24 $
 * @author mosir
 */
class AppmarketForm extends Model
{
	public $aid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(!$post->title) {
			$this->errors = Language::get('title_empty');
			return false;
		}
		// if(!isset($post->config->period) || empty($post->config->period)) {
		// 	$this->errors = Language::get('select_period');
		// 	return false;
		// }
		
		// add
		if(!$this->aid) {
			if(empty($post->appid)) {
				$this->errors = Language::get('appid_empty');
				return false;
			}
			if(AppmarketModel::find()->where(['appid' => $post->appid])->exists()) {
				$this->errors = Language::get('appid_existed');
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
	
		if(!$this->aid || !($model = AppmarketModel::findOne($this->aid))) {
			$model = new AppmarketModel();
			$model->appid = $post->appid;
			$model->add_time = Timezone::gmtime();
		}
        $model->category = $post->category;
		$model->title = addslashes($post->title);
		$model->summary = addslashes($post->summary);
		$model->price = $post->price;
		$model->description = $post->description;
		$model->status = $post->status;
		
		if($model->save()) {
			if(isset($post->fileVal) && ($logo = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_APPMARKET, $model->appid, $post->fileVal)) !== false) {
				$model->logo = $logo;
				$model->save();
			}
			
			// 附件入库
            if(isset($post->desc_file_id) && ($post->desc_file_id = ArrayHelper::toArray($post->desc_file_id))) {
				UploadedFileModel::updateAll(['item_id' => $model->aid], ['in', 'file_id', $post->desc_file_id]);
            }
			
			return $model;
		}
		return null;
	}
}
