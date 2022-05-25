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

use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\OrderGoodsModel;
use common\models\GcategoryModel;
use common\models\NavigationModel;
use common\models\ArticleModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Def;
use common\library\Page;

/**
 * @Id StoreController.php 2018.6.11 $
 * @author mosir
 */

class StoreController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('store');
		$this->params = ArrayHelper::merge($this->params, [
			'navs'	=> NavigationModel::getList()
		]);
	}
	
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\StoreForm();
		if(($store = $model->formData($post, 20)) === false) {
			return Message::warning($model->errors);
		}

		// 页面公共参数
		$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));

		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
		
		$this->params['page'] = Page::seo(['title' => $store['store_name']]);
        return $this->render('../store.index.html', $this->params);
    }
	
	public function actionSearch()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'cate_id', 'page']);
		
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
    	}
		if ($store['state'] == Def::STORE_APPLYING) {
			return Message::warning(Language::get('the_store_is_applying'));
		}
		
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.goods_name,g.price,g.default_image,gst.comments,gst.views,gst.sales')->joinWith('goodsStatistics gst', false)->where(['store_id' => $post->id, 'if_show' => 1, 'closed' =>  0]);
		if($post->keyword) {
			$query->andWhere(['like', 'goods_name', $post->keyword]);
			$search_name = sprintf(Language::get('goods_include'), $post->keyword);
		}
		if($post->cate_id > 0) {
			$cateIds = GcategoryModel::getDescendantIds($post->cate_id, $post->id);
			$goodsIds = \common\models\CategoryGoodsModel::find()->select('goods_id')->where(['in', 'cate_id', $cateIds])->column();
			$query->andWhere(['in', 'g.goods_id', $goodsIds]);
			$search_name = GcategoryModel::find()->select('cate_name')->where(['store_id' => $post->id, 'cate_id' => $post->cate_id])->scalar();
		}
		
		$orders = $this->getOrders();
		if($post->orderby && isset($orders[$post->orderby])) {
			$orderBy = explode('|', $post->orderby);
			$query->orderBy([$orderBy[0] => ($orderBy[1] == 'desc' ? SORT_DESC : SORT_ASC)]);
		}
		$page = Page::getPage($query->count(), 40);
		$goodslist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($goodslist as $key => $goods) {
            empty($goods['default_image']) && $goodslist[$key]['default_image'] = Yii::$app->params['default_goods_image'];
        }
		$this->params['goodslist'] = $goodslist;
		$this->params['pagination'] = Page::formatPage($page);
		
		$this->params['search_name'] = $search_name ? $search_name : Language::get('all_goods');
		
		// 页面公共参数
		$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
		
		$this->params['page'] = Page::seo(['title' => $this->params['search_name'] . ' - ' . $store['store_name']]);
        return $this->render('../store.search.html', $this->params);
	}
	
	public function actionArticle()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'article_id']);
		
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
    	}
		if ($store['state'] == Def::STORE_APPLYING) {
			return Message::warning(Language::get('the_store_is_applying'));
		}
		
		if(!$post->article_id || !($article = ArticleModel::find()->select('article_id,description')->where(['store_id' => $post->id, 'article_id' => $post->article_id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_article'));
		}
		$this->params['article'] = $article;
		
		// 页面公共参数
		$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
		
		$this->params['page'] = Page::seo(['title' => $article['title'] . ' - ' . $store['store_name']]);
        return $this->render('../store.article.html', $this->params);
    }
	
	public function actionCredit()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id','level']);
		
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			return Message::warning(Language::get('the_store_not_exist'));
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			return Message::warning(Language::get('the_store_is_closed'));
		}
		if ($store['state'] == Def::STORE_APPLYING) {
			return Message::warning(Language::get('the_store_is_applying'));
		}
    	
		// 页面公共参数
		$this->params = array_merge($this->params, ['store' => $store], Page::getAssign('store', $post->id));
		
		// 取得评价过的商品
		$query = OrderGoodsModel::find()->alias('og')->select('o.order_id, buyer_id, buyer_name, anonymous, evaluation_time, goods_id, goods_name, specification, price, quantity, goods_image, evaluation, comment')->joinWith('order o', false)->where(['seller_id' => $post->id, 'evaluation_status' => 1, 'is_valid' => 1])->orderBy(['evaluation_time' => SORT_DESC]);
		
		// 数据库字段记录的是5分制，3分为中评
		if(isset($post->level)) {
			if($post->level == 1) $query->andWhere(['<', 'evaluation', 3]);
			if($post->level == 2) $query->andWhere(['=', 'evaluation', 3]);
			if($post->level == 3) $query->andWhere(['>', 'evaluation', 3]);
		}

		$page = Page::getPage($query->count(), 20);
		$this->params['goodslist'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['pagination'] = Page::formatPage($page);
	
		// 按时间统计
        $list = array();
        foreach(['all', 'good', 'middle', 'bad'] as $value) {
            $list[$value]['in_a_week']        = 0;
            $list[$value]['in_a_month']       = 0;
            $list[$value]['in_six_month']     = 0;
            $list[$value]['six_month_before'] = 0;
            $list[$value]['total']            = 0;
        }
		$query = OrderGoodsModel::find()->alias('og')->select('evaluation_time, evaluation')->joinWith('order o', false)->where(['seller_id' => $post->id, 'evaluation_status' => 1, 'is_valid' => 1])->orderBy(['evaluation_time' => SORT_DESC]);
		foreach($query->asArray()->each() as $goods)
		{
			$level = 'good';
			if($goods['evaluation'] < 3) $level = 'bad';
			else if($goods['evaluation'] == 3) $level = 'middle';

			$list[$level]['total']++;
			$list['all']['total']++;
	
			$days = (Timezone::gmtime() - $goods['evaluation_time']) / (24 * 3600);
			if ($days <= 7)
			{
				$list[$level]['in_a_week']++;
				$list['all']['in_a_week']++;
			}
			if ($days <= 30)
			{
				$list[$level]['in_a_month']++;
				$list['all']['in_a_month']++;
			}
			if ($days <= 180)
			{
				$list[$level]['in_six_month']++;
				$list['all']['in_six_month']++;
			}
			if ($days > 180)
			{
				$list[$level]['six_month_before']++;
				$list['all']['six_month_before']++;
			}
		}
		$this->params['credits'] = $list;
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
		
		$this->params['page'] = Page::seo(['title' => Language::get('credit_evaluation') . ' - ' . $store['store_name']]);
        return $this->render('../store.credit.html', $this->params);
    }
	
	private function getOrders()
	{
		$orders = array(
            'add_time|desc' => Language::get('add_time_desc'),
            'price|asc' 	=> Language::get('price_asc'),
            'price|desc' 	=> Language::get('price_desc'),
			'sales|desc' 	=> Language::get('sales_desc'),
			'views|desc' 	=> Language::get('views_desc'),
        );
		return $orders;
	}
}