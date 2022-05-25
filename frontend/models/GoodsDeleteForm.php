<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model;

use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsImageModel;

/**
 * @Id GoodsDeleteForm.php 2018.8.14 $
 * @author mosir
 */
class GoodsDeleteForm extends Model
{
	public $goods_id = 0;
	public $store_id = 0;
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
		
		$dropIds = GoodsModel::find()->select('goods_id')->where(['in', 'goods_id', explode(',', $this->goods_id)])->andWhere(['store_id' => $this->store_id])->column();
		if(!empty($dropIds)) {
			if(GoodsModel::deleteAll(['in', 'goods_id', $dropIds])) {			
				// 删除商品附件（注意：删除图片会导致该商品如果已经被购买，那么订单列表中的商品图片加载不出来，导致页面显示缓慢）
				//UploadedFileModel::deleteGoodsFile($dropIds, $this->store_id);

				// 删除规格/图片信息
				GoodsSpecModel::deleteAll(['in', 'goods_id', $dropIds]);
				GoodsImageModel::deleteAll(['in', 'goods_id', $dropIds]);

			}
		}
		return true;
	}
}
