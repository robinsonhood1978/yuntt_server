<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\CollectModel;

use common\library\Basewind;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;
use common\library\Resource;
use common\models\StoreModel;

/**
 * @Id FavoriteController.php 2018.11.15 $
 * @author yxyc
 */

class FavoriteController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;

	/** 
	 * 获取我收藏的商品列表
	 * @api 接口访问地址: http://api.xxx.com/favorite/goods
	 */
    public function actionGoods()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = CollectModel::find()->alias('c')->select('c.type,g.goods_id,g.goods_name,g.price,g.default_image as goods_image,g.store_id,g.cate_id')->joinWith('goods g', false)->where(['userid' => Yii::$app->user->id, 'c.type' => 'goods'])->orderBy(['c.add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/** 
	 * 获取我收藏的店铺列表
	 * @api 接口访问地址: http://api.xxx.com/favorite/store
	 */
    public function actionStore()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = CollectModel::find()->alias('c')->select('c.type,s.store_id,s.store_name,s.store_logo,s.credit_value')->joinWith('store s', false)->where(['userid' => Yii::$app->user->id, 'c.type' => 'store'])->orderBy(['c.add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['store_logo'] = Formatter::path($value['store_logo'], 'store');
			$list[$key]['credit_image'] = Resource::getThemeAssetsUrl(['file' => 'images/credit/' . StoreModel::computeCredit($value['credit_value']), 'baseUrl' => Basewind::homeUrl()]);
		}
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取我的收藏的数量（商品或店铺）
	 * @api 接口访问地址: http://api.xxx.com/favorite/quantity
	 */
    public function actionQuantity()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$query = CollectModel::find()->alias('c')->joinWith('goods g', false)->where(['userid' => Yii::$app->user->id]);
		
		// 根据类型读取店铺或商品
		if(isset($post->type) && in_array($post->type, ['goods', 'store'])) {
			$query->andWhere(['c.type' => $post->type]);
		}

		return $respond->output(true, null, ['quantity' => $query->count()]);
    }
}