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

use common\models\UserModel;
use common\models\OrderModel;
use common\models\OrderGoodsModel;
use common\models\CategoryStoreModel;
use common\models\ScategoryModel;
use common\models\GcategoryModel;
use common\models\ArticleModel;
use common\models\GoodsModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Resource;
use common\library\Def;

/**
 * @Id StoreModel.php 2018.4.3 $
 * @author mosir
 */

class StoreModel extends ActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%store}}';
	}

	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'store_id']);
	}
	// 关联表
	public function getGoods()
	{
		return parent::hasMany(GoodsModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getGcategory()
	{
		return parent::hasMany(GcategoryModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getCategoryStore()
	{
		return parent::hasOne(CategoryStoreModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getSgrade()
	{
		return parent::hasOne(SgradeModel::className(), ['grade_id' => 'sgrade']);
	}

	/**
	 * 不要加条件(state=1)，其他地方需要获取店铺状态参数
	 */
	public static function getInfo($store_id = 0)
	{
		$result = parent::find()->alias('s')->select('s.*,u.userid,u.username,u.email,u.phone_mob')->joinWith('user u', false)->where(['store_id' => $store_id])->asArray()->one();
		if (!empty($result['certification'])) {
			$result['certifications'] = explode(',', $result['certification']);
		}
		if (!empty($result['swiper'])) {
			$result['swiper'] = json_decode($result['swiper'], true);
		}
		return $result;
	}

	/* 获取店铺页面公共数据 */
	public static function getStoreAssign($store_id = 0, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {
			$store = self::getInfo($store_id);
			if (!empty($store)) {
				$store['credit_image'] = Resource::getThemeAssetsUrl('images/credit/' . StoreModel::computeCredit($store['credit_value']));
				$store = array_merge($store, RegionModel::getArrayRegion($store['region_name'], $store['region_name']));
				
				// 店铺动态评分
				$store['dynamicEvaluation'] = self::dynamicEvaluation($store_id);

				if (Basewind::getCurrentApp() == 'pc') {
					$store['gcategories'] = GcategoryModel::getTree($store_id);
				}

				if (Basewind::getCurrentApp() == 'wap') {

					// 店铺在售商品总数
					$store['goods_count'] = GoodsModel::getCountOfStore($store_id);

					// 店铺被收藏数
					$store['collects'] = CollectModel::find()->where(['type' => 'store', 'item_id' => $store_id])->count();
				}
			}
			$data = $store;

			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}


	/**
	 * 根据信用值计算图标
	 * @param int $credit_value 信用值
	 * @return string 图片文件名
	 */
	public static function computeCredit($credit_value = 0)
	{
		list($level, $number) = self::getLevelByValue($credit_value);

		if ($level == 'level_end') {
			return 'level_end.gif';
		}

		return $level . '_' . $number . '.gif';
	}

	/**
	 * 各信用等级需要的credit_value值
	 * @return int $credit_value
	 */
	public static function creditLevel()
	{
		$step = intval(Yii::$app->params['upgrade_required']);
		if ($step < 1) $step = 5;

		$heart = $step * 5;
		$diamond = $heart * 6;
		$crown = $diamond * 6;

		return [$step, $heart, $diamond, $crown];
	}

	/**
	 * 获取信用等级名称
	 * @desc 默认升一级需要5倍分值
	 */
	public static function getLevelByValue($credit_value = 0)
	{
		list($step, $heart, $diamond, $crown) = self::creditLevel();

		if ($credit_value < $heart) {
			return ['heart', floor($credit_value / $step) + 1];
		}
		if ($credit_value < $diamond) {
			return ['diamond', floor(($credit_value - $heart) / $heart) + 1];
		}
		if ($credit_value < $crown) {
			return ['crown', floor(($credit_value - $diamond) / $diamond) + 1];
		}

		return ['level_end', ''];
	}

	/**
	 * 通过等级获取对应的信用度值及下一级的需要的信用度值
	 */
	public static function getValueByLevel($level)
	{

		list($step, $heart, $diamond, $crown) = self::creditLevel();
		if ($level == 'heart') {
			return [0, $heart];
		}
		if ($level == 'diamond') {
			return [$heart, $diamond];
		}
		if ($level == 'crown') {
			return [$diamond, $crown];
		}

		return array(0, -1);
	}

	/**
	 * 根据星级打分计算
	 * @desc 小于三颗星为差评=-1分，等于三颗星为中评=0分，大于3颗星=1分（也可以在此拓展半分模式）
	 */
	public static function evaluationToValue($evaluation = 3)
	{
		return $evaluation < 3 ? -1 : ($evaluation == 3 ? 0 : 1);
	}

	/**
	 * 获取行业店铺的平均值
	 */
	public static function getIndustryAvgEvaluation($store_id = 0, $cached = true)
	{
		// 获取该店铺所在的行业
		if (($cate_id = CategoryStoreModel::find()->select('cate_id')->where(['store_id' => $store_id])->scalar())) {
			$allId = ScategoryModel::getDescendantIds($cate_id);

			// 获取该行业下的所有店铺
			$stores = parent::find()->alias('s')->select('s.store_id')->joinWith('categoryStore cs', false)->where(['s.state' => 1])->andWhere(['in', 'cs.cate_id', $allId])->column();

			// 获取行业服务评分/物流评分均值
			return self::recountDynamicEvaluation(array_values($stores), $cached);
		}

		return array(0, 0);
	}

	/**
	 * 获取店铺动态评分数据并与行业比较
	 * @param bool $cached 是否获取缓存数据（数据缓存一天，由于读取的数据量大，建议使用缓存数据）
	 */
	public static function dynamicEvaluation($store_id = 0, $cached = true)
	{
		// 本店铺的动态评分
		list($goodsEvaluation, $serviceEvaluation, $shippedEvaluation, $comprehensiveEvaluation) = self::recountDynamicEvaluation($store_id, $cached);

		// 行业店铺动态评分
		list($industryGoodsEvaluation, $industryServiceEvaluation, $industryShippedEvaluation) = self::getIndustryAvgEvaluation($store_id, $cached);

		$result = array(
			'goods' => [
				'value' => sprintf("%.4f", $goodsEvaluation),
				'industry_value' => sprintf("%.4f", $industryGoodsEvaluation),
				'compare' => self::compareIndustry($goodsEvaluation - $industryGoodsEvaluation)
			],
			'service' => [
				'value' => sprintf("%.4f", $serviceEvaluation),
				'industry_value' => sprintf("%.4f", $industryServiceEvaluation),
				'compare' => self::compareIndustry($serviceEvaluation - $industryServiceEvaluation)
			],
			'shipped' => [
				'value' => sprintf("%.4f", $shippedEvaluation),
				'industry_value' => sprintf("%.4f", $industryShippedEvaluation),
				'compare' => self::compareIndustry($shippedEvaluation - $industryShippedEvaluation)
			],
			'comprehensive' => [
				'value' => sprintf("%.4f", $comprehensiveEvaluation),
				'percentage'  => round($comprehensiveEvaluation / 5, 4) * 100 . '%'
			]
		);

		return $result;
	}

	/**
	 * 与行业对比数据
	 */
	public static function compareIndustry($value = 0)
	{
		$name = $value > 0 ? 'high' : ($value < 0 ? 'low' : 'equal');
		return array('value' => abs($value) * 100 . '%', 'name' => $name);
	}

	/**
	 * 计算店铺动态评分值/或行业店铺动态评分值
	 * @param int|array $ids 店铺ID列表
	 */
	public static function recountDynamicEvaluation($ids = [], $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__) . var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if ($data === false || !$cached) {

			// 如果获取行业店铺评分，则会是多个店铺ID
			if (!is_array($ids)) {
				$ids = [$ids];
			}

			// 商品评分(描述相符)
			$goodsEvaluation = 5.0000;

			// 服务评分
			$serviceEvaluation = 5.0000;

			// 物流评分
			$shippedEvaluation = 5.0000;

			// 这里取最多6000条交易记录，也可以像淘宝一样，取近6个月的交易完成的数据
			$query = OrderModel::find()->select('order_id,service_evaluation,shipped_evaluation')
				->with(['orderGoods' => function ($q) {
					$q->where(['is_valid' => 1]); // 取有效评价
				}])
				->where(['in', 'seller_id', $ids])->andWhere(['evaluation_status' => 1])
				->orderBy(['evaluation_time' => SORT_DESC])->limit(6000);

			$goodsValue = $goodsNum = $serviceValue = $shippedValue = 0;
			foreach ($query->asArray()->all() as $key => $value) {
				foreach ($value['orderGoods'] as $v) {
					$goodsValue += $v['evaluation'];
					$goodsNum++;
				}
				$serviceValue += $value['service_evaluation'];
				$shippedValue += $value['shipped_evaluation'];
			}
			if ($query->count() > 0) {
				$goodsEvaluation = $goodsNum > 0 ? round($goodsValue / $goodsNum, 4) : 0;
				$serviceEvaluation = round($serviceValue / $query->count(), 4);
				$shippedEvaluation = round($shippedValue / $query->count(), 4);
			}

			// 综合评分值
			$comprehensiveEvaluation = round(($goodsEvaluation + $serviceEvaluation + $shippedEvaluation) / 3, 4);

			$data = [$goodsEvaluation, $serviceEvaluation, $shippedEvaluation, $comprehensiveEvaluation];
			$cache->set($cachekey, $data, 3600 * 24);
		}

		return $data;
	}

	/**
	 * 计算好评率
	 * @desc 如果数据量大，也可以取近期6个月的交易数据来计算好评率
	 */
	public static function recountPraiseRate($store_id = 0)
	{
		// 评价总数
		$list = OrderGoodsModel::find()->alias('og')->select('og.evaluation')->joinWith('order o', false)
			->where(['og.is_valid' => 1, 'o.seller_id' => $store_id, 'o.evaluation_status' => 1])
			->orderBy(['o.evaluation_time' => SORT_DESC])
			->asArray()->all();

		$total = $good = 0;
		foreach ($list as $value) {
			if (self::evaluationToValue($value['evaluation']) > 0) {
				$good++;
			}
			$total++;
		}

		if ($total == 0) {
			return 0;
		}

		// 计算好评数占总数的百分比（存值0-100）
		return round($good / $total, 4) * 100;
	}
}
