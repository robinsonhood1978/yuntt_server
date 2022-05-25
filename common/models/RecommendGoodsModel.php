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

use common\models\GoodsModel;
use common\models\GcategoryModel;
use common\models\GuideshopModel;

use common\library\Promotool;

/**
 * @Id RecommendGoodsModel.php 2018.8.14 $
 * @author mosir
 */

class RecommendGoodsModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%recommend_goods}}';
    }
	
	/**
     * 取得某推荐下商品
     * @param   int     $recom_id       推荐类型
     * @param   int     $num            取商品数量
     * @param   bool    $default_image  如果商品没有图片，是否取默认图片
     * @param   int     $mall_cate_id   分类（最新商品用到）
     */
    public static function getRecommendGoods($recom_id = 0, $num = 10, $default_image = true, $mall_cate_id = 0, $timeslot = false, $sort_by = false, $cached = true)
    {
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$query = GoodsModel::find()->alias('g')->select('g.goods_id, g.goods_name, g.default_image, gs.price, gs.stock,gs.spec_id,gst.sales,s.store_id,s.store_name')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->joinWith('goodsDefaultSpec gs', false)->where(['g.if_show' => 1, 'g.closed' => 0, 's.state' => 1]);
		
			// 分类最新商品
			if ($recom_id == -100)
			{
				if ($mall_cate_id > 0){
					$query->andWhere(['in', 'g.cate_id', GcategoryModel::getDescendantIds($mall_cate_id)]);
				}
			}
			// 推荐类型商品
			else {
				$query->andWhere(['recom_id' => $recom_id]);
				$query->joinWith('recommendGoods rg', false);
			}

			// 因社区团购购买流程只在移动端体现，所以PC端排除社区团购商品，如果无需排除，可注释该代码
			if(($childs = GuideshopModel::getCategoryId(true)) !== false) {
				$query->andWhere(['not in', 'g.cate_id', $childs]);
			}
			
			// 时间段
			if($timeslot) {
				$query->andWhere(['and', ['>=', 'g.add_time', $timeslot['begin']], ['<=', 'g.add_time', $timeslot['end']]]);
			}
			// 排序
			if(empty($sort_by) || ($sort_by == 'add_time')){
				$query->orderBy(['g.add_time' => SORT_DESC]);
			}elseif(in_array($sort_by,array('views','collects','comments','sales'))){
				$query->orderBy(['gst.'.$sort_by => SORT_DESC, 'g.add_time' => SORT_DESC]);
			}
			
			if($num <= 0 || $num > 100) $num = 10;
			$list = $query->limit($num)->asArray()->all();
			
			$promotool = Promotool::getInstance()->build();
			foreach($list as $key => $value) 
			{
				if($default_image && empty($value['default_image'])) {
					$list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
				}
				
				$result = $promotool->getItemProInfo($value['goods_id'], $value['default_spec']);
				if($result !== false) {
					$list[$key]['old_price'] = $value['price'];
					$list[$key]['price'] = $result['price'];
				}
			}
			
			$data = $list;
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
    }
}
