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

use common\models\GoodsSpecModel;
use common\models\GoodsStatisticsModel;

use common\library\Def;

/**
 * @Id GoodsModel.php 2018.3.16 $
 * @author mosir
 */

class GoodsModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%goods}}';
	}
	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getGcategory()
	{
		return parent::hasOne(GcategoryModel::className(), ['cate_id' => 'cate_id']);
	}

	// 关联表
	public function getGoodsDefaultSpec()
	{
		return parent::hasOne(GoodsSpecModel::className(), ['spec_id' => 'default_spec']);
	}
	// 关联表
	public function getGoodsSpec()
	{
		return parent::hasMany(GoodsSpecModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getGoodsImage()
	{
		return parent::hasMany(GoodsImageModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getGoodsStatistics()
	{
		return parent::hasOne(GoodsStatisticsModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getGoodsPvs()
	{
		return parent::hasMany(GoodsPvsModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getGoodsIntegral()
	{
		return parent::hasOne(GoodsIntegralModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getBrand()
	{
		return parent::hasOne(BrandModel::className(), ['brand_name' => 'brand']);
	}
	// 关联表
	public function getSgcategory()
	{
		return parent::hasMany(GcategoryModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getRecommendGoods()
	{
		return parent::hasOne(RecommendGoodsModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getReport()
	{
		return parent::hasOne(ReportModel::className(), ['goods_id' => 'goods_id']);
	}

	/*
     * 取得店铺商品数量
     */
	public static function getCountOfStore($store_id = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$data = parent::find()->where(['store_id' => $store_id, 'if_show' => 1, 'closed' => 0])->count();
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}

	/**
	 * 取得商品库存数量
	 */
	public static function getStocks($goods_id = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$data = GoodsSpecModel::find()->select('stock')->where(['goods_id' => $goods_id])->sum('stock');
			$cache->set($cachekey, $data, 3600);
		}
		return intval($data);
	}

	/* 清除缓存 */
	public static function clearCache($goods_id = 0)
	{
		$cache = Yii::$app->cache;
		$cachekey = 'page_of_goods_' . $goods_id;
		$cache->delete($cachekey);
	}

	/**
	 * 设置/获取商品浏览历史 
	 * @var boolean $supple 补全
	 */
	public static function history($id = 0, $num = 10, $supple = false)
	{
		$result = array();
		$allId  = array();

		// 安全考虑用request
		$cookies = Yii::$app->request->cookies;
		if (isset($cookies['goodsBrowseHistory']) && ($allId = explode(',', $cookies['goodsBrowseHistory']->value))) {
			$list = parent::find()->select('goods_id,goods_name,default_image, price')->where(['and', ['if_show' => 1, 'closed' => 0], ['in', 'goods_id', $allId]])->indexBy('goods_id')->asArray()->all();

			// 用for确保顺序不变
			for ($i = count($allId) - 1; $i >= 0; $i--) {
				if (!isset($list[$allId[$i]])) {
					unset($allId[$i]);
					continue;
				}

				if (count($result) < $num) {
					$result[] = $list[$allId[$i]];
				}
			}

			if($supple && count($result) < $num) {
				$list = parent::find()->select('goods_id,goods_name,default_image, price')->where(['if_show' => 1, 'closed' => 0])->limit($num - count($result))->asArray()->all();
				$result = array_merge($result, $list);
			}
		}
		if ($id) $allId[] = $id;
		$allId = array_values(array_unique($allId));
		if (count($allId) > Def::GOODS_COLLECT) {
			unset($allId[0]);
		}

		// 用response才能add
		$cookies = Yii::$app->response->cookies;
		$cookies->add(new \yii\web\Cookie([
			'name' => 'goodsBrowseHistory',
			'value' => join(',', $allId),
			'expire' => time() + 3600 * 24 * 10
		]));

		return $result;
	}
}
