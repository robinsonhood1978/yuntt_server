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

use common\models\GoodsModel;
use common\models\GcategoryModel;

use common\library\Language;

/**
 * @Id GoodsForm.php 2018.8.14 $
 * @author mosir
 */
class GoodsForm extends Model
{
	public $goods_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(!($allId = explode(',', $this->goods_id))) {
			$this->errors = Language::get('no_goods_selected');
			return false;
		}
		if(isset($post->goods_name) && empty($post->goods_name)) {
			$this->errors = Language::get('goods_name_empty');
			return false;
		}
		if(isset($post->cate_id) && ($post->cate_id > 0) && GcategoryModel::find()->where(['parent_id' => $post->cate_id])->exists()) {
			$this->errors = Language::get('select_end_gcategory');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		foreach(GoodsModel::find()->select('goods_id')->where(['in', 'goods_id', explode(',', $this->goods_id)])->each() as $model) {
			if($post->cate_id > 0) {
				$model->cate_id = $post->cate_id;
				$model->cate_name = $post->cate_name;
			}
			if($post->closed >= 0) {
				$model->closed = $post->closed;
				$model->close_reason = ($post->closed == 1) ? $post->close_reason : '';
			}
			// for ajax editcol
			if(isset($post->goods_name) && !empty($post->goods_name)) $model->goods_name = $post->goods_name;
			if(isset($post->brand) && !empty($post->brand)) $model->brand = $post->brand;
			if(isset($post->if_show)) $model->if_show = $post->if_show;
			if($model->save() === false) {
				$this->errors = $model->errors;
				return false;
			}
		}
		return true;
	}
}
