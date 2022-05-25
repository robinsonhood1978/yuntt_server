<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\web\Response;

use common\models\GoodsModel;
use common\models\StoreModel;
use common\models\SgradeModel;
use common\models\GcategoryModel;
use common\models\ScategoryModel;
use common\models\RegionModel;
use common\models\OrderModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Resource;
use common\library\Page;
use common\library\Def;

/**
 * @Id SearchController.php 2018.4.29 $
 * @author mosir
 */

class SearchController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'), [
			'navs'	=> NavigationModel::getList()
		]);
	}

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'region_id']);
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.price, s.store_id,s.store_name,s.im_qq,gst.sales,gst.comments')->where(['g.if_show' => 1, 'g.closed' => 0, 's.state' => 1])->with('goodsImage')->joinWith('goodsStatistics gst', false)->orderBy(['g.goods_id' => SORT_DESC]);
		
		$model = new \frontend\models\SearchForm();
		$query = $model->getConditions($post, $query);
		
		if($query->count() > 0) {
			$page = Page::getPage($query->count(), 50);
			$goodsList = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			$this->params['pagination'] = ['top' => Page::formatPage($page, true, 'simple'), 'bottom' => Page::formatPage($page, true, 'lg')];
		}
		else
		{
			$goodsList = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.price, s.store_id,s.store_name,s.im_qq,gst.sales,gst.comments')
				->joinWith('store s', false)
				->with('goodsImage')
				->joinWith('goodsStatistics gst', false)
				->where(['g.if_show' => 1, 'g.closed' => 0, 's.state' => 1])
				->orderBy(['g.goods_id' => SORT_DESC])
				->limit(50)->asArray()->all();
				
			$this->params['goodsListEmptyRecommended'] = true;
		}
		foreach ($goodsList as $key => $goods) {
			empty($goods['default_image']) && $goodsList[$key]['default_image'] = Yii::$app->params['default_goods_image'];
		}
		$this->params['goods_list'] = $goodsList;
		
		// 底部推荐商品
		$this->params['recommend_goods'] = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.default_image,g.price,gst.sales')->joinWith('goodsStatistics gst', false)->limit(5)->orderBy(['gst.views' => SORT_DESC])->asArray()->all();
		
		// 品牌旗舰店
		$this->params['flagstore'] = \common\models\FlagstoreModel::getFlagstore($post);
		
		// 按分类/品牌/价格/属性/地区统计
		$this->params = array_merge($this->params, $model->getSelectors($post));
		
		// 读取所有省份列表
		$this->params['provinces'] = $this->getProvinces($post);
		
		// 排序
		$this->params['orders'] = $model->getOrders();

		// 取得选中条件
        $this->params['filters'] = $model->getFilters($post);
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/jquery.lazyload.js,search_goods.js,cart.js',
		]);
		
		$this->params['page'] = Page::seo($this->getPageTitle($post));
        return $this->render('../search.goods.html', $this->params);
    }
	
	public function actionStore()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'recommended', 'sgrade', 'credit_value', 'page']);
		
		$query = StoreModel::find()->alias('s')->select('s.store_id,s.store_name,s.owner_name,s.address,s.sgrade,s.credit_value,s.praise_rate,s.state,s.add_time,s.recommended,s.store_logo,s.im_qq')->joinWith('categoryStore cs', false)->where(['state' => Def::STORE_OPEN]);
		
		$model = new \frontend\models\SearchForm();
		$query = $model->getStoreConditions($post, $query);
		
		$page = Page::getPage($query->count(), 10);
		$storelist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($storelist as $key => $store)
		{
			$storelist[$key]['goodslist'] = GoodsModel::find()->select('goods_id,goods_name,store_id,default_image,price')->where(['store_id' => $store['store_id'], 'if_show' => 1, 'closed' => 0])->orderBy(['add_time' => SORT_DESC])->limit(10)->asArray()->all();
			$storelist[$key]['sgrade_name'] = SgradeModel::find()->select('grade_name')->where(['grade_id' => $store['sgrade']])->scalar();
			$storelist[$key]['credit_image'] = Resource::getThemeAssetsUrl('images/credit/' . StoreModel::computeCredit($store['credit_value']));
			
			empty($store['store_logo']) && $storelist[$key]['store_logo'] = Yii::$app->params['default_store_logo'];
			$storelist[$key]['sales'] = OrderModel::find()->where(['seller_id' => $store['store_id'], 'status' => Def::ORDER_FINISHED])->count();
			
			// 店铺在售商品总数
			$storelist[$key]['goods_count'] = GoodsModel::getCountOfStore($store['store_id']);
			
			//店铺动态评分
			$storelist[$key]['dynamicEvaluation'] = StoreModel::dynamicEvaluation($store['store_id']);
		}
		
		$this->params['storelist'] = $storelist;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 店铺分类
		$this->params['scategories'] = ScategoryModel::getTree(true, 2);
		
		// 店铺排序
		$this->params['orders'] = $model->getStoreOrders();
		
		// 店铺等级
		$this->params['sgrades'] = SgradeModel::find()->select('grade_name')->indexBy('grade_id')->orderBy(['grade_id' => SORT_ASC])->column();
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js,search_store.js');
		$this->params['_curlocal'] = array(
			array('text' => Language::get('all_categories'), 'url' => Url::toRoute(['search/store']))
		);
		
		$this->params['page'] = Page::seo($this->getPageTitle($post, 'store'));
        return $this->render('../search.store.html', $this->params);
	}
	
	public function actionGetcity($cached = true)
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export($post, true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$provincesIds = array();
			$provinces = RegionModel::getList(RegionModel::getProvinceParentId());
			foreach($provinces as $key => $val) {
				$provincesIds[] = $val['region_id'];
			}
			if(in_array($post->region_id, $provincesIds)) {
				$provinceId = $post->region_id;
			}
			else
			{
				$provinceId = RegionModel::find()->select('parent_id')->where(['region_id' => $post->region_id])->scalar();
				!in_array($provinceId, $provincesIds) && $provinceId = null;
			}
			
			if($provinceId !== null) {
				$cities = RegionModel::getList($provinceId);
				foreach($cities as $key => $val) {
					if($val['region_id'] == $post->region_id) {
						$cities[$key]['selected'] = true;
						break;
					}
				}
				$data = ['done' => true, 'retval' => $cities];
				
				$cache->set($cachekey, $data, 3600);
			}
		}
		Yii::$app->response->format = Response::FORMAT_JSON;
		return $data;
	}
	
	private function getProvinces($post = null, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$provincesIds = array();
			$provinces = RegionModel::getList(RegionModel::getProvinceParentId());
			foreach($provinces as $key => $val) 
			{
				$provincesIds[] = $val['region_id'];
				if(($val['region_id'] == $post->region_id) || ($val['region_id'] == RegionModel::find()->select('parent_id')->where(['region_id' => $post->region_id])->scalar())) {
					$provinces[$key]['selected'] = true;
				}
			}
			
			// 设置选中的省，城市
			if(in_array($post->region_id, $provincesIds)) {
				$province = RegionModel::find()->select('region_name')->where(['region_id' => $post->region_id])->scalar();
			}
			else
			{
				$province = null;
				if(($city = RegionModel::find()->select('parent_id,region_name')->where(['region_id' => $post->region_id])->one())) {
					$province = RegionModel::find()->select('region_name')->where(['region_id' => $city->parent_id])->scalar();
					$city = $city->region_name;
				}
			}
			$data = ['list' => $provinces, 'selected' => ['province' => $province, 'city' => $city]];
			
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
	
	private function getPageTitle($post = null, $sType = 'goods')
	{
		$title = null;
		if($post->keyword) {
			$title = $post->keyword;
		}
		elseif($post->brand) {
			$title = $post->brand;
		}
		elseif($post->cate_id) {
			if($sType == 'goods') {
				$title = GcategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id])->scalar();
			} else $title = ScategoryModel::find()->select('cate_name')->where(['cate_id' => $post->cate_id])->scalar();
		}
		else {
			$title = ($sType == 'store') ?  Language::get('searched_store') : Language::get('searched_goods');
		}
		return ($title === null) ? array() : ['title' => $title];
	}
}