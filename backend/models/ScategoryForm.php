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

use common\models\ScategoryModel;

use common\library\Language;

/**
 * @Id ScategoryForm.php 2018.8.16 $
 * @author mosir
 */
class ScategoryForm extends Model
{
	public $cate_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->cate_name)) {
			$this->errors = Language::get('cate_name_empty');
			return false;
		}
		
		// edit
		if($this->cate_id) {
			
			// 不能将当前分类的上级设置到当前分类的下级
			if(($childId = ScategoryModel::getDescendantIds($this->cate_id, true, false, false))) {
				if(in_array($post->parent_id, $childId)) {
					$this->errors = Language::get('parent_error');
					return false;
				}
			}
			if($this->cate_id == $post->parent_id) {
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
		
		if(!$this->cate_id || !($model = ScategoryModel::findOne($this->cate_id))) {
			$model = new ScategoryModel();
		}
		
        $model->cate_name = $post->cate_name;
		$model->parent_id = $post->parent_id;
		
		// 不应设置该值，理由为：如果修改了该值，那么该分类的下级分类理应也要修改为相应的显示或隐藏，但该操作也有有可能修改分类的上级
		// 这样会导致控制了新的下级分类的显示或隐藏，极不合理
		// $model->if_show = $post->if_show;
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		return $model;
	}
}
