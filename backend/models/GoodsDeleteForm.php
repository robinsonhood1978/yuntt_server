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

use common\models\GoodsModel;
use common\models\StoreModel;
use common\models\GoodsSpecModel;
use common\models\GoodsIntegralModel;
use common\models\GoodsStatisticsModel;
use common\models\GoodsPvsModel;
use common\models\CategoryGoodsModel;

use common\library\Basewind;

/**
 * @Id GoodsDeleteForm.php 2018.8.14 $
 * @author mosir
 */
class GoodsDeleteForm extends Model
{
	public $goods_id = 0;
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
		
		foreach(explode(',', $this->goods_id) as $id) {
			if($model = GoodsModel::find()->select('goods_id,goods_name,store_id')->where(['goods_id' => $id])->one()) {
				if($model->delete() === false) {
					$this->errors = $model->errors;
					return false;
				}
				// 删除商品规格
				GoodsSpecModel::deleteAll(['goods_id' => $id]);
				// 删除商品积分
				GoodsIntegralModel::deleteAll(['goods_id' => $id]);
				// 删除商品属性
				GoodsPvsModel::deleteAll(['goods_id' => $id]);
				// 删除商品统计
				GoodsStatisticsModel::deleteAll(['goods_id' => $id]);
				// 删除商品分类关联表
				CategoryGoodsModel::deleteAll(['goods_id' => $id]);
				
				// 删除商品附件（注意：删除图片会导致该商品如果已经被购买，那么订单列表中的商品图片加载不出来，导致页面显示缓慢）
				//UploadedFileModel::deleteGoodsFile($id, false);
				
				$store = StoreModel::find()->select('store_name')->where(['store_id' => $model->store_id])->asArray()->one();
				
				// 发站内信
				$pmer = Basewind::getPmer('toseller_goods_drop_notify', ['goods' => ArrayHelper::toArray($model), 'store' => $store, 'reason' => $post->content, 'baseUrl' => Basewind::homeUrl()]);
				if($pmer) {
					$pmer->sendFrom(0)->sendTo($model->store_id)->send();
				}
			}
		}
		return true;
	}
}
