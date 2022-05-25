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

use common\models\GcategoryModel;
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Def;

/**
 * @Id GcategoryAdForm.php 2018.8.13 $
 * @author mosir
 */
class GcategoryAdForm extends Model
{
	public $cate_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(!$this->cate_id) {
			$this->errors = Language::get('no_such_gcategory');
			return false;
		}
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!$this->cate_id || !($gcategory = GcategoryModel::findOne($this->cate_id))) {
			$gcategory = new GcategoryModel();
		}

		if(isset($post->fileVal) && ($image = UploadedFileModel::getInstance()->upload($post->fileVal, 0, Def::BELONG_GCATEGORY_AD, $gcategory->cate_id, $post->fileVal)) !== false) {
			$gcategory->ad = $image;
			return $gcategory->save() ? $gcategory : null;
		}
		return null;
	}
}
