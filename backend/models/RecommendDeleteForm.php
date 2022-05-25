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

use common\models\RecommendModel;
use common\models\RecommendGoodsModel;

/**
 * @Id RecommendDeleteForm.php 2018.8.14 $
 * @author mosir
 */
class RecommendDeleteForm extends Model
{
	public $recom_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		foreach(explode(',', $this->recom_id) as $id) {
			if($model = RecommendModel::find()->where(['recom_id' => $id])->one()) {
				if($model->delete() === false) {
					$this->errors = $model->errors;
					return false;
				}
				RecommendGoodsModel::deleteAll(['recom_id' => $id]);
			}
		}
		return true;
	}
}
