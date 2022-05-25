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

use common\models\GoodsModel;
use common\models\GcategoryModel;
use common\models\IntegralSettingModel;
use common\models\NavigationModel;

use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Resource;

/**
 * @Id IntegralController.php 2018.9.10 $
 * @author mosir
 */

class IntegralController extends \common\controllers\BaseMallController
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
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			return Message::warning(Language::get('integral_disabled'));
		}

		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.default_image,g.goods_name,g.price,g.add_time,gi.max_exchange,s.store_id,s.store_name,gst.sales')->joinWith('goodsIntegral gi', false)->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['>', 'gi.max_exchange', 0]])->orderBy(['g.add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), 20);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		$rate = IntegralSettingModel::getSysSetting('rate');
		foreach($list as $key => $value)
		{
			$list[$key]['exchange_rate'] = floatval($rate);
			$list[$key]['exchange_integral'] = floatval($value['max_exchange']);
			$list[$key]['exchange_money'] = round($value['max_exchange'] * $rate, 2);
			$exchange_price = $value['price'] - $list[$key]['exchange_money'];
			if($exchange_price < 0) {
				$list[$key]['exchange_integral'] = round($value['price'] / $rate, 2);
				$list[$key]['exchange_money'] = floatval($value['price']);
				$exchange_price = 0;
			}
			$list[$key]['exchange_price'] = $exchange_price;
			empty($value['default_image']) && $list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
		}  
		$this->params['goodslist'] = $list;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();

		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.lazyload.js');
	
		$this->params['page'] = Page::seo(['title' => Language::get('integral_list')]);
		return $this->render('../integral.index.html', $this->params);
	}
}