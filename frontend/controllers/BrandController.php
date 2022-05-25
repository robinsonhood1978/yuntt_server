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

use common\models\GcategoryModel;
use common\models\BrandModel;
use common\models\GoodsModel;
use common\models\CollectModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id BrandController.php 2018.7.20 $
 * @author MH
 */

class BrandController extends \common\controllers\BaseMallController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = BrandModel::find()->where(['if_show' => 1])->orderBy(['sort_order' => SORT_ASC,'recommended'=> SORT_DESC, 'brand_id' => SORT_ASC]);
			if($post->cate_id) {
				$query->andWhere(['cate_id' => $post->cate_id]);
			}
			$page = Page::getPage($query->count(), $post->pageper);
			$brandsList = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($brandsList), 'totalPage' => $page->getPageCount());
		}
		else
		{
			$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
			$this->params['categories'] = GcategoryModel::getList(0,0);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
			
			$this->params['page'] = Page::seo(['title' => Language::get('all_brands')]);
       	 	return $this->render('../brand.index.html', $this->params);
		}	
	}
	
	public function actionView()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'page', 'pageper']);
		
		if(Yii::$app->request->isAjax)
		{
			$query = GoodsModel::find()->where(['if_show' => 1, 'closed' => 0, 'brand_id' => $post->id])->joinWith('goodsStatistics gst',false)->orderBy(['sales' => SORT_DESC]);
			$page = Page::getPage($query->count(), $post->pageper);		
			$goodsList = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			return array('result' => array_values($goodsList), 'totalPage' => $page->getPageCount());
		}
		else
		{			
			$this->params['collects'] = CollectModel::find()->where(['item_id' => $post->id,  'type' => 'brand'])->count();
			$this->params['goods_count'] = GoodsModel::find()->select('goods_id')->where(['if_show' => 1, 'closed' => 0, 'brand_id' => $post->id])->count();
			
			$brand = BrandModel::find()->where(['brand_id' => $post->id])->asArray()->one();
			if(empty($brand)) {
				return Message::warning(Language::get('no_such_brand'));
			}
			$this->params['brand'] = $brand;
			$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
	
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.infinite.js');
			$this->params['infiniteParams'] = json_encode(ArrayHelper::toArray($post));
					
			$this->params['page'] = Page::seo(['title' => Language::get('brand_goods')]);
       	 	return $this->render('../brand.view.html', $this->params);
		}
	}
}