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

use common\models\CartModel;
use common\models\GoodsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id CartController.php 2018.7.3 $
 * @author mosir
 */

class CartController extends \common\controllers\BaseMallController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'));
	}
	
	public function actionIndex()
	{
		if(!Yii::$app->request->isPost)
		{
			// 感兴趣的商品
			$this->params['interest'] = GoodsModel::find()->alias('g')->select('g.goods_id,goods_name,price,sales,default_image')->joinWith('goodsStatistics gst', false)->orderBy(['views' => SORT_DESC, 'collects' => SORT_DESC, 'sales' => SORT_DESC])->limit(6)->asArray()->all();
			
			$model = new \frontend\models\CartForm();
			$carts = $model->formData($this->params['carts_top']);
			if(empty($carts['list'])) {
				return $this->cartEmpty();
			}
			
			// 店铺满折满减
			$carts['list'] = $model->getCartFullprefer($carts['list']);	
			
			// 是否显示领取优惠券按钮
			$carts['list'] = $model->getCouponEnableReceive($carts['list']);	
			$this->params['carts'] = $carts;
		
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js, dialog/dialog.js,cart.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('cart')]);
			return $this->render('../cart.index.html', $this->params);
		}
		else
		{
			if(Yii::$app->user->isGuest) {
				return $this->redirect(Yii::$app->user->loginUrl);
			}
			
			$buy = Basewind::trimAll(Yii::$app->request->post('buy'));
			
			// 过滤掉不是购物车中的商品，或者是购物车中的商品但不是自己的商品
			if(empty($buy) || !is_array($buy) || !($selected = CartModel::find()->select('rec_id')->where(['userid' => Yii::$app->user->id])->andWhere(['in', 'product_id', array_keys($buy)])->column())) {
				return Message::warning(Language::get('select_empty_by_cart'));	
			}
			// 到此，可以认为是正常的购买数据
			
			// 保存选中的商品
			CartModel::updateAll(['selected' => 0], ['and', ['userid' => Yii::$app->user->id], ['not in', 'rec_id', $selected]]);
			CartModel::updateAll(['selected' => 1], ['in', 'rec_id', $selected]);
			return $this->redirect(['order/normal']);
		}
	}
	
	/* 把商品放入购物车 */
    public function actionAdd()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['spec_id', 'quantity', 'selected']);
		
		$model = new \frontend\models\CartForm();
		if(($result = $model->valid($post)) === false) {
			return Message::warning($model->errors);
		}
		
		// 用于识别购物车产品唯一性的ID
		$product_id = Yii::$app->cart->getId($result);
		if(($product = Yii::$app->cart->getItem($product_id)->getProduct())) {
			if($result['stock'] < $product->quantity + $post->quantity) {
				Yii::$app->cart->remove($product->product_id);
			}
		} else $product = Yii::$app->cart->createItem(array_merge($result, ['product_id' => $product_id]));
		
		// 放入购物车（自动判断是加入商品，还是增加已加入商品的数量）
		Yii::$app->cart->put($product, $post->quantity);
		
		// 立即购买的操作（确保只购买的是当前立即购买的商品）
		if($post->selected) {
			Yii::$app->cart->unchoses();
			Yii::$app->cart->chose($product->product_id);
			Yii::$app->cart->change($product->product_id, $post->quantity, $result['price']);
		}
		return Message::result($model->getCart(), Language::get('add_cart_successed'));		
	}

	/**
	 * 批量加入购物车
	 */
    public function actionMany()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['selected']);
		$post->specs = json_decode($post->specs, true);

		$list = array();
		$model = new \frontend\models\CartForm();
		foreach($post->specs as $key => $value) {
			if(($value = intval($value)) && $value > 0) {
				if(($list[] = $model->valid((Object)['spec_id' => $key, 'quantity' => $value])) === false) {
					return Message::warning($model->errors);
				}
			}
		}
		
		foreach($list as $key => $result)
		{
			// 用于识别购物车产品唯一性的ID
			$product_id = Yii::$app->cart->getId($result);
			if(($product = Yii::$app->cart->getItem($product_id)->getProduct())) {
				if($result['stock'] < $product->quantity + $result['quantity']) {
					Yii::$app->cart->remove($product->product_id);
				}
			} else $product = Yii::$app->cart->createItem(array_merge($result, ['product_id' => $product_id]));
			
			// 放入购物车（自动判断是加入商品，还是增加已加入商品的数量）
			Yii::$app->cart->put($product, $result['quantity']);

			// 立即购买的操作（确保只购买的是当前立即购买的商品）
			if($post->selected) {
				if($key == 0) Yii::$app->cart->unchoses();
				Yii::$app->cart->chose($product->product_id);
				Yii::$app->cart->change($product->product_id, $result['quantity'], $result['price']);
			}
		}
		
		return Message::result($model->getCart(), Language::get('add_cart_successed'));
	}
	
	/* 修改数量 */
	public function actionUpdate()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['spec_id', 'quantity']);
		
		$model = new \frontend\models\CartForm();
		if(($result = $model->valid($post)) === false) {
			return Message::warning($model->errors);
		}
		
		$product_id = Yii::$app->cart->getId($result);
		Yii::$app->cart->change($product_id, $post->quantity, $result['price']);
		
		return Message::result($model->getCart(), Language::get('update_item_successed'));
	}
	
	/* 删除购物车商品 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		$model = new \frontend\models\CartForm();
		if(!$post->product_id || !($product = Yii::$app->cart->getItem($post->product_id)->getProduct()) || ($product->userid != Yii::$app->user->id) || !(Yii::$app->cart->remove($post->product_id))) {
			return Message::warning(Language::get('drop_item_failed'));
		}
		return Message::result($model->getCart(), Language::get('drop_item_successed'));
	}

	/**
 	 * 设置购物车商品为选中/取消状态
	 */
    public function actionChose()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['selected']);
		$post->product_ids = json_decode($post->product_ids);

		// 先全部取消
		Yii::$app->cart->unchoses();
		foreach($post->product_ids as $product_id) {
			Yii::$app->cart->chose($product_id, $post->selected);
		}

		$model = new \frontend\models\CartForm();
		return Message::result($model->getCart());
	}
	
	/* 购物车为空 */
    public function cartEmpty()
    {
		$this->params['page'] = Page::seo(['title' => Language::get('cart')]);
		return $this->render('../cart.empty.html', $this->params);
    }
}