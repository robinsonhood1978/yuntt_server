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

/**
 * @Id RecommendCancelForm.php 2018.8.14 $
 * @author mosir
 */
class RecommendCancelForm extends Model
{
	public $goods_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		return true;
	}
	
	public function cancel($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		return RecommendGoodsModel::deleteAll(['in', 'goods_id', explode(',', $this->goods_id)]);;
	}
}
