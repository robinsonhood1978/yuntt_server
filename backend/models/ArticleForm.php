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

use common\models\ArticleModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id ArticleForm.php 2018.8.22 $
 * @author mosir
 */
class ArticleForm extends Model
{
	public $article_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->title)) {
			$this->errors = Language::get('title_empty');
			return false;
		}
		if(!$post->cate_id) {
			$this->errors = Language::get('cate_empty');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->article_id || !($model = ArticleModel::findOne($this->article_id))) {
			$model = new ArticleModel();
			$model->add_time = Timezone::gmtime();
		}
		
        $model->title = $post->title;
		$model->cate_id = $post->cate_id;
		$model->if_show = $post->if_show; 
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		$model->description = $post->description;
		$model->link = $post->link ? $post->link : '';

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		// 附件入库
		if(isset($post->desc_file_id)) {
			$post->desc_file_id = ArrayHelper::toArray($post->desc_file_id);
			if(!empty($post->desc_file_id) && is_array($post->desc_file_id)) {
				UploadedFileModel::updateAll(['item_id' => $model->article_id], ['in', 'file_id', $post->desc_file_id]);
			}
		}
		
		return true;
	}
}
