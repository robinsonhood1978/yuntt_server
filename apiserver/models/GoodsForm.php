<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GoodsPropModel;
use common\models\GoodsPvsModel;
use common\models\GoodsPropValueModel;
use common\models\GcategoryModel;
use common\models\GuideshopModel;
use common\models\IntegralSettingModel;
use common\models\DeliveryTemplateModel;
use common\models\RegionModel;
use common\models\CollectModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

use apiserver\library\Formatter;

/**
 * @Id GoodsForm.php 2018.10.23 $
 * @author yxyc
 */
class GoodsForm extends Model
{
	public $goods_id = 0;
	public $errors = null;
	
	public function formData($post)
	{
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.store_id,g.goods_name,g.tags,g.if_show,g.cate_id,g.brand,g.spec_qty,g.spec_name_1,g.spec_name_2,g.add_time,g.default_spec,g.default_image,g.video, g.recommended,g.price,g.mkprice,g.dt_id,gs.stock,s.store_name,s.sgrade,gi.max_exchange,gst.sales,gst.comments,gst.views,gst.collects');
		if(isset($post->querydesc) && ($post->querydesc === true)) {
			$query->addSelect('g.description,g.content');
		}
		
		$query = $query->joinWith('store s', false)->joinWith('goodsDefaultSpec gs', false)->joinWith('goodsIntegral gi', false)->joinWith('goodsStatistics gst', false)
			->where(['g.goods_id' => $post->goods_id]);
		
		if(isset($post->if_show)) {
			if($post->if_show) {
				$query->andWhere(['g.if_show' => 1, 'g.closed' => 0, 's.state' => Def::STORE_OPEN]);
			} else {
				$query->andWhere(['or', ['g.if_show' => 0], ['g.closed' => 1], ['!=', 's.state' => Def::STORE_OPEN]]);
			}
		}
		
		if(($record = $query->asArray()->one()))
		{
			$integral = array('enabled' => false);

			// 积分功能开启状态下
			if(IntegralSettingModel::getSysSetting('enabled')) {
				$integral['enabled'] = true;

				// 购买该商品可以使用的积分数
				$integral['exchange_rate'] = IntegralSettingModel::getSysSetting('rate');
				$integral['exchange_money'] = floor($integral['exchange_rate'] * $record['max_exchange']);
				$integral['exchange_integral'] = $record['max_exchange'];

				// 计算送积分值
				$buygoods = IntegralSettingModel::getSysSetting('buygoods');
				if($buygoods && ($giveRate = $buygoods[$record['sgrade']])) {
					$integral['give_integral'] = floor($giveRate * $record['price']);
				}
			}
			unset($record['max_exchange'], $record['sgrade']);
			$record['integral'] = $integral;
			$record['default_image'] = Formatter::path($record['default_image'], 'goods');
			$record['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);
			$record['category'] = GcategoryModel::getAncestor($record['cate_id'], 0, false);

			// 商品是否被当前访客收藏
			if(!Yii::$app->user->isGuest) {
				$record['becollected'] = CollectModel::find()->where(['type' => 'goods', 'item_id' => $post->goods_id, 'userid' => Yii::$app->user->id])->exists();
			}
		}
		
		return $record;
	}
	
	/*
	 * 获取基础搜索条件
	 */
	public function getBasicConditions($post = null, $query = null)
	{
		// 指定店铺
		if(isset($post->store_id) && $post->store_id) {
			$query->andWhere(['s.store_id' => $post->store_id]);
		}
		
		// 指定关键词
		if(isset($post->keyword) && $post->keyword) {
			$query->andWhere(['or', ['like', 'g.goods_name', $post->keyword], ['like', 'g.brand', $post->keyword]]);
		}
		
		// 是否推荐
		if(isset($post->recommended)) {
			$query->andWhere(['g.recommended' => $post->recommended]);
		}
		
		// 指定分类
		if(isset($post->cate_id) && $post->cate_id) {
			$allId = GcategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'g.cate_id', $allId]);
		}

		// 指定社区团购商品
		if($post->channel == 'community') {
			if(($childs = GuideshopModel::getCategoryId(true)) !== false && !in_array($post->cate_id, $childs)) {
				$query->andWhere(['in', 'g.cate_id', $childs]);
			}
		}
		
		// 排序
		if(isset($post->orderby) && in_array($post->orderby, ['sales|desc','price|desc','price|asc','views|desc','add_time|desc', 'add_time|asc', 'comments|desc'])) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC, 'g.goods_id' => SORT_DESC]);
		} else $query->orderBy(['gst.sales' => SORT_DESC, 'g.goods_id' => SORT_DESC]);

		return $query;
	}
	
	/**
	 * 获取搜索条件
	 */
	public function getConditions($post = null, $query = null)
	{
		if($query === null) {
			$query = GoodsModel::find()->alias('g')->joinWith('goodsStatistics gst', false)->where(['s.state' => 1]);
		}
		$query->joinWith('store s', false)->joinWith('goodsDefaultSpec gs', false)->joinWith('goodsPvs gp', false);
		
		$query = $this->getBasicConditions($post, $query);
		
		// 是否上架
		if(isset($post->if_show)) {
			$query->andWhere(['g.if_show' => $post->if_show]);
		}
		
		// 是否禁售
		if(isset($post->closed)) {
			$query->andWhere(['g.closed' => $post->closed]);
		}

		// 指定品牌
		if(isset($post->brand) && $post->brand) {
			$query->andWhere(['brand' => $post->brand]);
		}
		
		// 指定价格区间
		if(isset($post->price) && $post->price) {
			$min = trim(explode('-', $post->price)[0]);
            $max = trim(explode('-', $post->price)[1]);
            $min > 0 && $query->andWhere(['>=', 'price', $min]);
            $max > 0 && $query->andWhere(['<=', 'price', $max]);
		}
		
		// 指定地区
		if(isset($post->region_id) && $post->region_id) {
			$query->andWhere(['s.region_id' => $post->region_id]);
		}
		
		// 指定商品属性
		if(isset($post->props) && $post->props)
		{
			foreach(explode('|', $post->props) as $k => $pv)
			{
				// 监测是否全为数字
				if(is_numeric(str_replace(':','', $pv))){
					$query->andWhere("instr(gp.pvs,:pv$k) > 0", [":pv$k" => $pv]);
				}
			}
		}
		
		return $query;
	}
	
	/*
	 * 在一定的搜索条件下，获取还可用的商品检索字典
	 */
	public function getSelectors($post)
	{
		$result = array();
		
		// 按分类统计(把下级分类的商品数量也计算到父级)
		$by_category = array();
		$queryByCategory = $this->getConditions($post)->select('count(*) as count,g.cate_id')->groupBy('g.cate_id')->orderBy(['count' => SORT_DESC])->asArray()->all();
		foreach($queryByCategory as $key => $val) {
			if(($group = GcategoryModel::getParnetEnd($val['cate_id'], $post->cate_id)) !== false) {
				if(isset($by_category[$group[0]])) {
					$by_category[$group[0]]['count'] += $val['count'];
				} else $by_category[$group[0]] = ['cate_id' => $group[0], 'cate_name' => $group[1], 'count' => $val['count']];
			}
		}
		$result['by_category'] = array_values($by_category);
			
		// 按品牌统计
		$by_brand = $this->getConditions($post)->select('count(*) as count,g.brand,b.brand_logo')->joinWith('brand b', false)->andWhere(['b.if_show' => 1])->groupBy('g.brand')->orderBy(['count' => SORT_DESC])->asArray()->all();
		foreach($by_brand as $key => $value) {
			$by_brand[$key]['brand_logo'] = Formatter::path($value['brand_logo']);
		}
		$result['by_brand'] = $by_brand;
			
		// 按价格统计
		$by_price = array();
		$priceMin = $this->getConditions($post)->min('g.price');
		$priceMax = min($this->getConditions($post)->max('g.price'), 10000);
		$priceStep = max(ceil(($priceMax - $priceMin) / 5), 50);
		$queryByPrice = $this->getConditions($post)->select("FLOOR((g.price-'{$priceMin}')/{$priceStep}) AS i, count(*) as count")->groupBy('i')->orderBy(['i' => SORT_ASC])->orderBy(['count' => SORT_DESC])->asArray()->all();
		foreach($queryByPrice as $key => $val) {
			$min = $priceMin + $val['i'] * $priceStep;
			$max = $priceMin + ($val['i'] + 1) * $priceStep;
			$by_price[] = array(
				'value' => $min . '-' . $max,
				'name'  => $min .'-'. $max,
				'count' => $val['count'],
			);
		}
		$result['by_price'] = $by_price;
		
		// 按属性统计
		$by_prop = array();
		$queryByProps = $this->getConditions($post)->select('gp.pvs')->andWhere(['<>', 'gp.pvs', ''])->column();
		$pvs = array_unique(explode(';', implode(';', array_values($queryByProps))));
		sort($pvs, SORT_DESC);
			
		// 检查属性名和属性值是否存在，有可能是之前有，但后面删除了
		foreach($pvs as $key => $val) 
		{
			$item = explode(':', $val);
			if(!GoodsPropModel::find()->select('pid')->where(['pid' => $item[0], 'status' => 1])->exists()) {
				unset($pvs[$key]);
			}
			elseif(!GoodsPropValueModel::find()->where(['pid' => $item[0], 'vid' => $item[1], 'status' => 1])->exists()) {
				unset($pvs[$key]);
			}
		}
		// 当前选中的属性数组
		$propChecked = array();
		if($post->props) {
			$propChecked = array_unique(explode('|', $post->props));
			foreach($propChecked as $key => $val) {
				list($p, $v) = explode(':', $val);
				$propChecked[] = $p;
				unset($propChecked[$key]);
			}
			$propChecked = array_unique($propChecked);
			sort($propChecked);
		}

		$pid = 0;
		foreach($pvs as $key => $val)
		{
			$item = explode(':', $val);
				
			// 将选中的排除
			if(!in_array($item[0], $propChecked))
			{
				$prop = GoodsPropModel::find()->select('pid,name,is_color')->where(['pid' => $item[0], 'status' => 1])->one();
				$by_prop[$prop->pid] = ArrayHelper::toArray($prop);
					
				// 不是同一个pid的属性值，不做累加
				if($pid != $prop->pid) {
					$propValue = array();
					$pid = $prop->pid;
				}
				$propValue[] = GoodsPropValueModel::find()->select('vid,pid,pvalue as val,color')->where(['pid' => $item[0], 'vid' => $item[1], 'status' => 1])->asArray()->one();
				$by_prop[$prop->pid] += array('values' => $propValue);
			}
		}
		// 统计每个属性有多少商品数
		if($by_prop) 
		{
			foreach($by_prop as $key => $val)
			{
				if(!isset($val['values']) || empty($val['values'])) {
					unset($by_prop[$key]);continue;
				}
				foreach($val['values'] as $k => $v)
				{
					$by_prop[$key]['values'][$k]['count'] = $this->getConditions($post)->select('g.goods_id')->andWhere('instr(gp.pvs,:pv) > 0', [':pv' => $v['pid'].':'.$v['vid']])->count();
				}
			}
			$result['by_prop'] = array_values($by_prop);
		}
			
		// 按地区统计
		$by_region = $this->getConditions($post)->select('count(*) as count,s.region_id,s.region_name')->joinWith('store s', false)->andWhere(['>', 's.region_id', 0])->groupBy('s.region_id')->orderBy(['count' => SORT_DESC])->asArray()->all();
		$result['by_region'] = $by_region;
		
		return array('selectors' => $result);
	}
	
	/* 
	 * 取得选中条件 
	 */
    public function getFilters($post = null)
    {
        static $filters = null;
        if ($filters === null)
        {
            $filters = array();
            if($post->keyword) {
                $filters['keyword'] = array('key' => 'keyword', 'name' => Language::get('keyword'), 'value' => $post->keyword);
            }
			if($post->brand) {
				$filters['brand'] = array('key' => 'brand', 'name' => Language::get('brand'), 'value' => $post->brand);
			}
			if($post->region_id && ($region = RegionModel::find()->select('region_name')->where(['region_id' =>  $post->region_id])->one())) {
				$filters['region'] = array('key' => 'region_id', 'name' => Language::get('region'), 'value' => $region->region_name);
			}
			if($post->price) {
				list($priceMin, $priceMax) = Basewind::trimAll(explode('-', $post->price));
                if($priceMin <= 0) {
                    $filters['price'] = array('key' => 'price', 'name' => Language::get('price'), 'value' => Language::get('le') . $priceMax);
                }
                elseif($priceMax <= 0) {
                    $filters['price'] = array('key' => 'price', 'name' => Language::get('price'), 'value' => Language::get('ge') . $priceMin);
                }
                else{
                    $filters['price'] = array('key' => 'price', 'name' => Language::get('price'), 'value' => $priceMin . '-' . $priceMax);
                }
			}
			if($post->props) {
				foreach(explode('|',$post->props) as $val)
				{
					$pv = explode(':', $val);
					if(is_numeric($pv[0]) && is_numeric($pv[1]))
					{
						$propName = GoodsPropModel::find()->select('name')->where(['pid' => $pv[0], 'status' => 1])->scalar();
						$propValue = GoodsPropValueModel::find()->select('pvalue')->where(['pid' => $pv[0], 'vid' => $pv[1], 'status' => 1])->scalar();
						if($propName && $propValue) {
							$filters['props'.$pv[0]] = array('key' => $val, 'name' => $propName, 'value'=> $propValue);
						}
					}
				}
			}
			if($post->cate_id) {
				$filters['category'] = array('key' => 'cate_id', 'name' => Language::get('gcategory'), 'value' => GcategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id])->scalar());
			}
        }
        return array('filters' => $filters);
    }
	
	/* 
	 * 获取商品属性信息
	 */
	public function getGoodProps($goods_id = 0)
	{
		$result = array();
		if(($pvs = GoodsPvsModel::find()->select('pvs')->where(['goods_id' => $goods_id])->scalar())) 
		{
			$pvs = explode(';', $pvs);
			foreach($pvs as $pv)
			{
				if(!$pv) continue;
				$pv = explode(':', $pv);
				if(($prop = GoodsPropModel::find()->where(['pid' => $pv[0], 'status' => 1])->one())) {
					if(($value = GoodsPropValueModel::find()->where(['pid' => $prop->pid, 'vid' => $pv[1], 'status' => 1])->one())) {
						if(isset($result[$prop->pid]['value'])) $result[$prop->pid]['value'] .= '，' . $value->pvalue;
						else $result[$prop->pid] = array('name' => $prop->name, 'value' => $value->pvalue);
					}
				}
			}
		}
		$data = array_values($result);
			
		return $data;
	}
	
	/* 
	 * 获取指定城市的商品运费信息
	 * 返回多个运费方式(express/ems/post)的运费情况
	 */
	public function getLogistics($template_id, $city_id = 0, $store_id = 0)
	{
		if($template_id) {
			$delivery = DeliveryTemplateModel::find()->where(['template_id' => $template_id])->asArray()->one();
		}
		
		// 如果商品没有设置运费模板，取店铺默认的运费模板
		if(empty($delivery)) {
			$delivery = DeliveryTemplateModel::find()->where(['store_id' => $store_id])->orderBy(['template_id' => SORT_ASC])->asArray()->one();
				
			// 如果还是没有，添加一条默认的
			if(empty($delivery)) {
				$delivery = DeliveryTemplateModel::addFirstTemplate($store_id);
			}
		}
			
		// 如果不传城市ID，则通过IP自动识别所在城市
		if(!$city_id) {
			$city_id = RegionModel::getCityIdByIp();
		}
	
		$record = [
			'region' => [
				'region_id'=> $city_id,
				'region_name' => RegionModel::getRegionName($city_id),
			],
			'list' => DeliveryTemplateModel::getCityLogistic($delivery, $city_id), 
		];
		
		return $record;
	}
}
