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

use common\models\RegionModel;

use common\library\Language;

/**
 * @Id RegionForm.php 2018.8.13 $
 * @author mosir
 */
class RegionForm extends Model
{
	public $region_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->region_name)) {
			$this->errors = Language::get('region_name_empty');
			return false;
		}
		
		// edit
		if($this->region_id) {
			
			// 不能将当前地区的上级设置到当前地区的下级
			if(($childId = RegionModel::getDescendantIds($this->region_id, true, false, false))) {
				if(in_array($post->parent_id, $childId)) {
					$this->errors = Language::get('parent_error');
					return false;
				}
			}
			if($this->region_id == $post->parent_id) {
				$this->errors = Language::get('parent_error');
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
		
		if(!$this->region_id || !($model = RegionModel::findOne($this->region_id))) {
			$model = new RegionModel();
		}
		
        $model->region_name = $post->region_name;
		$model->parent_id = $post->parent_id;
		
		// 不应设置该值，理由为：如果修改了该值，那么该地区的下级地区理应也要修改为相应的显示或隐藏，但该操作也有有可能修改地区的上级
		// 这样会导致控制了新的下级地区的显示或隐藏，极不合理
		// $model->if_show = $post->if_show; 
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		
		return $model->save() ? $model : null;
	}
}
