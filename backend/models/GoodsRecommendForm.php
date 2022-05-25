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

use common\models\RecommendGoodsModel;

use common\library\Language;

/**
 * @Id GoodsRecommendForm.php 2018.8.14 $
 * @author mosir
 */
class GoodsRecommendForm extends Model
{
	public $goods_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(!$post->recom_id) {
			$this->errors = Language::get('recommend_required');
			return false;
		}
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		foreach(explode(',', $this->goods_id) as $id) {
			if(!($model = RecommendGoodsModel::find()->where(['goods_id' => $id])->one())) {
				$model = new RecommendGoodsModel();
			}
			$model->goods_id = $id;
			$model->recom_id = $post->recom_id;
			if($model->save() === false) {
				$this->errors = $model->errors;
				return false;
			}
		}
		return true;
	}
}
