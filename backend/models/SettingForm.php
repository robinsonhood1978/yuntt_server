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

use common\models\UploadedFileModel;

use common\library\Def;
use common\library\Setting;

/**
 * @Id SettingForm.php 2018.9.3 $
 * @author mosir
 */
class SettingForm extends Model
{
	public $errors = null;
	
	public function valid($post)
	{
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$imageFileds = ['site_logo', 'default_store_logo', 'default_goods_image', 'default_user_portrait'];
		foreach($imageFileds as $field)
		{
			if(!isset($post->$field) || empty($post->$field)) {
				unset($post->$field);
			}
	
			$fileFieldName = 'fileVal_'.$field;
			if(isset($post->$fileFieldName) && ($image = UploadedFileModel::getInstance()->upload($post->$fileFieldName, 0, Def::BELONG_SETTING, 0, $post->$fileFieldName)) !== false) {
				$post->$field = $image;
			}
			unset($post->$fileFieldName);
		}
		Setting::getInstance()->setAll($post);
		
		return true;
	}
}
