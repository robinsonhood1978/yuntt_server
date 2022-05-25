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

use common\models\BrandModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Def;

/**
 * @Id BrandForm.php 2018.8.13 $
 * @author mosir
 */
class BrandForm extends Model
{
	public $brand_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->brand_name)) {
			$this->errors = Language::get('brand_empty');
			return false;
		}
		
		// edit
		if($this->brand_id) {
			if(BrandModel::find()->where(['brand_name' => $post->brand_name])->andWhere(['!=', 'brand_id', $this->brand_id])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		
		// add
		else {
			if(BrandModel::find()->where(['brand_name' => $post->brand_name])->exists()) {
				$this->errors = Language::get('name_exist');
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

		if(!$this->brand_id || !($model = BrandModel::findOne($this->brand_id))) {
			$model = new BrandModel();
		}
		
		$model->cate_id = $post->cate_id;
        $model->brand_name = $post->brand_name;
		$model->recommended = $post->recommended;
		$model->if_show = $post->if_show; 
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		$model->tag = $post->tag;
		$model->letter = $post->letter;
		$model->description = $post->description;

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
			
		if(isset($post->fileVal) && ($image = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_BRAND_LOGO, $model->brand_id, $post->fileVal)) !== false) {
			$model->brand_logo = $image;
			$model->save();
		}
		if(isset($post->fileValBig) && ($image = UploadedFileModel::getInstance()->upload($post->fileValBig, 0, Def::BELONG_BRAND_IMAGE, $model->brand_id, $post->fileValBig)) !== false) {
			$model->brand_image = $image;
			$model->save();
		}
		return $model;
	}
}
