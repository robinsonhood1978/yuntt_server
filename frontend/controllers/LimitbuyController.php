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

use common\models\LimitbuyModel;
use common\models\GcategoryModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id LimitbuyController.php 2018.10.16 $
 * @author mosir
 */

class LimitbuyController extends \common\controllers\BaseMallController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['cate_id']);
		
		$query = LimitbuyModel::find()->alias('lb')->select('lb.id,lb.goods_id,lb.title,lb.start_time,lb.end_time,lb.store_id,lb.image,g.default_image,g.price,g.goods_name,g.default_spec,g.cate_id,s.store_name')->joinWith('goods g', false, 'INNER JOIN')->joinWith('store s', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['<=', 'lb.start_time', Timezone::gmtime()], ['>=', 'lb.end_time', Timezone::gmtime()]])->orderBy(['id' => SORT_DESC]);
		
		if($post->cate_id) {
			$childIds = GcategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cate_id', $childIds]);
		}
	
		$page = Page::getPage($query->count(), 20);
		$goodslist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		// 读取促销商品的类目，便于前台筛选
		$catlist = $post->cate_id ? array($post->cate_id) : array();
		
		$promotool = Promotool::getInstance()->build();
		foreach($goodslist as $key => $goods)
		{
			$result = $promotool->getItemProInfo($goods['goods_id'], $goods['default_spec']);
			$goodslist[$key]['pro_price'] = ($result !== false) ? $result['price'] : $goods['price'];
			empty($goods['default_image']) && $goodslist[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			
			$catlist[] = $goods['cate_id'];
		} 
		$this->params['goodslist'] = $goodslist;
		$this->params['pagination'] = ['top' => Page::formatPage($page, true, 'simple'), 'bottom' => Page::formatPage($page)];
		
		if(($catlist = array_unique($catlist))) {
			$ancestor = array();
			foreach($catlist as $key => $val) {
				!isset($ancestor[$val]) && $ancestor[$val] = GcategoryModel::getAncestor($val);
			}
			
			$categorys = array();
			foreach($ancestor as $key => $val) {
				!isset($categorys[$val[0]['cate_id']]) && $categorys[$val[0]['cate_id']] = $val[0];
				$categorys[$val[0]['cate_id']]['children'] = GcategoryModel::find()->select('cate_id,cate_name')->where(['parent_id' => $val[0]['cate_id']])->asArray()->all();
			}
			$this->params['categorys'] = $categorys;
		}
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		$this->params['_curlocal'] = $this->getCurlocal($post);
	
		$this->params['page'] = Page::seo(['title' => Language::get('limitbuy')]);
		return $this->render('../limitbuy.index.html', $this->params);
	}
	
	private function getCurlocal($post = null)
    {
		$curlocal = array();
        if ($post->cate_id)
        {
			$curlocal = array(array('text' => Language::get('all_categories'), 'url' => Url::toRoute('limitbuy/index')));
            $parents = GcategoryModel::getAncestor($post->cate_id);

			foreach($parents as $category) {
				$curlocal[] = array(
					'text' => $category['cate_name'], 'url' => Url::toRoute(['limitbuy/index', 'cate_id' => $category['cate_id']]));
			}
			unset($curlocal[count($curlocal) - 1]['url']);
		}
        return $curlocal;
    }
}