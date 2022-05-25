<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\models\BrandModel;

/**
 * @Id FlagstoreModel.php 2018.5.1 $
 * @author mosir
 */

class FlagstoreModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%flagstore}}';
    }
	
	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getBrand()
	{
		return parent::hasOne(BrandModel::className(), ['brand_id' => 'brand_id']);
	}
	// 关联表
	public function getGcategory()
	{
		return parent::hasOne(GcategoryModel::className(), ['cate_id' => 'cate_id']);
	}
	
	/* 搜索页获取品牌旗舰店 */
	public static function getFlagstore($post = null, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$query = parent::find()->alias('fs')->select('fs.description,s.store_id,s.store_logo,s.store_name')->joinWith('store s', false)->where(['status' => 1]);
			if($post->brand)
			{
				if(($brand = BrandModel::find()->select('brand_name,brand_logo')->where(['brand_name' => $post->brand])->one())) {
					$query->andWhere(['brand_id' => $brand->brand_id]);
				}
			}
			if($post->keyword) {
				$query->andWhere(['like', 'keyword', $post->keyword]);
			}
			if($post->cate_id) {
				$query->andWhere(['cate_id' => intval($post->cate_id)]);
			}
			$store = $query->asArray()->one();
			
			if($store) {
				empty($store['store_logo']) && $store['store_logo'] = Yii::$app->params['default_store_logo'];
				($brand && !empty($brand['brand_logo'])) && $store['store_logo'] = $brand['brand_logo'];
			}
			
			$data = $store ? array($store) : null;			
			$cache->set($cachekey, $data, 3600);
		}
		return $data;	
	}
}
