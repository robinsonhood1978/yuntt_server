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

use common\models\MealModel;
use common\models\MealGoodsModel;
use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsStatisticsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id MealForm.php 2018.10.23 $
 * @author mosir
 */
class MealForm extends Model
{
	public $id = 0;
	public $errors = null;
	
	/**
	 * 兼容API接口获取数据
	 */
	public function formData($post = null, $queryitem = true, $orderBy = [], $ifpage = false, $pageper = 10, $isAJax = false, $curPage = false)
	{
		// 缺省条件时，查询所有搭配购
		$query = MealModel::find()->alias('m')->select('m.meal_id,m.created,m.title,m.price as mealPrice,m.status,s.store_id,s.store_name')->joinWith('store s', false)->where(['status' => 1]);

		// 查询的是某个具体的搭配购
		if($this->id) {
			$query->andWhere(['meal_id' => $this->id]);
		}
		// 查询的是某个商品参与的所有搭配购
		elseif($post->goods_id) {
			$allId = MealGoodsModel::find()->select('meal_id')->where(['goods_id' => $post->goods_id])->column();
			$query->andWhere(['in', 'meal_id', $allId]);
		}
		if($queryitem) {
			$query->with(['mealGoods' => function($model) {
				$model->alias('mg')->select('mg.meal_id,g.goods_id,g.goods_name,g.default_image as goods_image,price,spec_name_1,spec_name_2,default_spec as spec_id,spec_qty')->joinWith('goods g', false);
			}]);
		}
		if($orderBy && !empty($orderBy)) {
			$query->orderBy($orderBy);
		}
		if(!empty($post->keyword)) {
			$query->andWhere(['or', ['like', 'title', $post->keyword], ['like', 'keyword', $post->keyword]]);
		}
		if($post->store_id) {
			$query->andWhere(['s.store_id' => $post->store_id]);
		}

		if(!$ifpage) {
			$list = $query->asArray()->all();
		} else {
			$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		}

		foreach($list as $key => $value)
		{
			$items = $value['mealGoods'];
			unset($list[$key]['mealGoods']);
			if($queryitem && empty($items)) {
				$list[$key]['status'] = 0; // 设为失效
			}
			if(!empty($items)) {
				$allPrice = 0;
				foreach($items as $k => $v) {
					$allPrice += $v['price'];
					$items[$k]['goods_image'] = Page::urlFormat($v['goods_image'], Yii::$app->params['default_goods_image']);
					if(($specs = GoodsSpecModel::find()->select('goods_id,price,spec_1,spec_2,spec_id,spec_image as image')->where(['goods_id' => $v['goods_id']])->asArray()->all())) {
						foreach($specs as $k1 => $v1) {
							$specs[$k1]['image'] = Page::urlFormat($v1['image']);
						}
						$items[$k]['specs'] = $specs;
						$items[$k]['sales'] = GoodsStatisticsModel::find()->select('sales')->where(['goods_id' => $v['goods_id']])->scalar();
						$items[$k]['specification'] = $this->getDefaultSpecification($v, $specs);

						if(Basewind::getCurrentApp() != 'api') {
							$items[$k]['unispecs'] = $v['spec_qty'] > 0 ? $this->formatSpecs($specs) : array();
						}
					}
				}
				$list[$key]['price'] = $allPrice;
			}
			$list[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $value['created']);
			$list[$key]['items'] = $list[$key]['status'] ? $items : array();
		}

		return array($list, $page);
	}

	/**
	 * 获取默认规格的组合值
	 */
	private function getDefaultSpecification($goods, $specs = [])
	{
		$specification = '';

		if(empty($specs)) {
			return $specification;
		}
		foreach($specs as $key => $value) {
			if($value['spec_id'] == $goods['spec_id']) {
				$specification = ($goods['spec_name_1'] ? $goods['spec_name_1'] . ':' . $value['spec_1'] : '') 
					. ' ' . ($goods['spec_name_2'] ?  $goods['spec_name_2'] . ':' . $value['spec_2'] : '');
			}
		}

		return $specification;
	}

	/**
	 * 获取不重复的规格数组
	 */
	private function formatSpecs($specs = [])
	{
		// 去重复
		$spec_1 = [];
		$spec_2 = [];  
		foreach($specs as $key => $value)
		{
			$spec_1[$key] = $value['spec_1'];
			$spec_2[$key] = $value['spec_2'];
		}
		$spec_1 = array_unique($spec_1);
		$spec_2 = array_unique($spec_2);
		
		// 暂时不考虑规格图片问题
		$format = array();
		foreach($spec_1 as $key => $value) {
			$format[$key] = array('name' => $value);
		}
		$spec_1 = $format;
		
		$format = array();
		foreach($spec_2 as $key => $value) {
			$format[$key] = array('name' => $value);
		}
		$spec_2 = $format;

		return array('spec_1' => $spec_1, 'spec_2' => $spec_2);
	}
}