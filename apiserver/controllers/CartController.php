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
use yii\helpers\ArrayHelper;

use common\library\Basewind;
use common\library\Language;

use apiserver\library\Respond;

/**
 * @Id CartController.php 2018.11.2 $
 * @author yxyc
 */

class CartController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取购物车商品，包含多个店铺的商品
	 * @api 接口访问地址: http://api.xxx.com/cart/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		$model = new \apiserver\models\CartForm();
		$carts = $model->formData($model->getCart());
		
		// 店铺满折满减
		$carts['list'] = $model->getCartFullprefer($carts['list']);
		
		// 是否显示领取优惠券按钮
		$carts['list'] = $model->getCouponEnableReceive($carts['list']);
		
		return $respond->output(true, null, $carts);
	}
	
	/**
	 * 单个商品插入购物车
	 * @api 接口访问地址: http://api.xxx.com/cart/add
	 */
    public function actionAdd()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id', 'quantity', 'selected']);
		
		$model = new \apiserver\models\CartForm();	
		if(($result = $model->valid($post)) === false) {
			return $respond->output($model->code, $model->errors);
		}
		$this->addCart([$result], $post);

		return $respond->output(true, Language::get('add_cart_successed'),  $model->getCart());
	}

	/**
	 * 批量加入购物车
	 * @api 接口访问地址: http://api.xxx.com/cart/many
	 */
    public function actionMany()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['selected']);
		
		$list = array();
		$model = new \apiserver\models\CartForm();
		foreach($post->specs as $key => $value) {
			if(($value = intval($value)) && $value > 0) {
				if(($list[] = $model->valid((Object)['spec_id' => $key, 'quantity' => $value])) === false) {
					return $respond->output($model->code, $model->errors);
				}
			}
		}
		$this->addCart($list, $post);

		return $respond->output(true, Language::get('add_cart_successed'), $model->getCart());
	}
	
	/**
	 * 更新购物车商品
	 * @api 接口访问地址: http://api.xxx.com/cart/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['spec_id', 'quantity', 'selected']);
		
		$model = new \apiserver\models\CartForm();
		if(($result = $model->valid($post)) === false) {
			return $respond->output($model->code, $model->errors);
		}
		
		// 用于识别购物车产品唯一性的ID
		$product_id = Yii::$app->cart->getId($result);
		Yii::$app->cart->change($product_id, $post->quantity, $result['price']);

		// 如果是设置选中/取消操作
		if(isset($post->selected)) {
			Yii::$app->cart->chose($product_id, $post->selected);
		}
		
		return $respond->output(true, Language::get('update_item_successed'), $model->getCart());
	}

	/**
 	 * 删除整个购物车商品
	 * @api 接口访问地址: http://api.xxx.com/cart/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}  
		
		// 业务参数
		//$post = Basewind::trimAll($respond->getParams(), true);
		Yii::$app->cart->clear();

		return $respond->output(true);	
	}
	
	/**
 	 * 移除购物车商品
	 * @api 接口访问地址: http://api.xxx.com/cart/remove
	 */
    public function actionRemove()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}  
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		$model = new \apiserver\models\CartForm();
		if(!$post->product_id || !($product = Yii::$app->cart->getItem($post->product_id)->getProduct()) || ($product->userid != Yii::$app->user->id) || !(Yii::$app->cart->remove($post->product_id))) {
			return $respond->output(Respond::CURD_FAIL, Language::get('drop_item_failed'));
		}
		
		return $respond->output(true, Language::get('drop_item_successed'), $model->getCart());
	}
	
	/**
 	 * 设置购物车商品为选中/取消状态
	 * @api 接口访问地址: http://api.xxx.com/cart/chose
	 */
    public function actionChose()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['selected']);
		
		$model = new \apiserver\models\CartForm();		
		if(!isset($post->product_ids) || empty($post->product_ids)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('params_invalid'));
		}
	
		$allId = ArrayHelper::toArray($post->product_ids);
		foreach($allId as $product_id) {
			Yii::$app->cart->chose(trim($product_id), !isset($post->selected) ? 1 : $post->selected);
		}
		
		return $respond->output(true, Language::get('update_item_successed'), $model->getCart());
	}

	/**
	 * 执行加入到购物车操作，兼容一个或多个商品
	 * @param array $list
	 */
	private function addCart($list = [], $post = null)
	{
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
	}
}