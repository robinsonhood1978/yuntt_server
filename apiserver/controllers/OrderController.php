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

use common\models\OrderModel;
use common\models\OrderGoodsModel;
use common\models\OrderExtmModel;
use common\models\DepositTradeModel;
use common\models\RefundModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Business;
use common\library\Plugin;
use common\library\Def;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id OrderController.php 2019.11.20 $
 * @author yxyc
 */

class OrderController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;
	
	/**
	 * 获取所有订单列表数据
	 * @api 接口访问地址: http://api.xxx.com/order/list
	 */
    public function actionList()
	{
		// TODO
	}
	
	/**
	 * 获取单条订单信息
	 * @api 接口访问地址: http://api.xxx.com/order/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.gtype,o.otype,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name,o.status,o.evaluation_status,o.payment_code,o.payment_name,o.express_no,o.express_company,o.goods_amount,o.order_amount,o.postscript,o.memo,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.evaluation_time,o.guider_id,oe.shipping_fee')
			->joinWith('orderExtm oe', false)->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['o.order_id' => $post->order_id]);

		if(!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}
		if(($trade = DepositTradeModel::find()->select('tradeNo,bizOrderId,bizIdentity')->where(['bizOrderId' => $record['order_sn'], 'bizIdentity' => Def::TRADE_ORDER])->asArray()->one())) {
			$record = array_merge($record, $trade);
			if(($refund = RefundModel::find()->select('refund_sn,status as refund_status')->where(['tradeNo' => $trade['tradeNo']])->andWhere(['!=', 'status', 'CLOSED'])->asArray()->one())) {
				$record = array_merge($record, $refund);
			}
		}
		$model = new \apiserver\models\OrderForm();
		$record = $model->formatDate($record);

		return $respond->output(true, null, $record);
	}
	
	/**
	 * 提交预支付购物订单
	 * @api 接口访问地址: http://api.xxx.com/order/create
	 */
	public function actionCreate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		// 购物车/搭配套餐/拼团订单/社区团购订单
		if(!isset($post->otype) || !in_array($post->otype, ['normal', 'meal', 'teambuy', 'guidebuy'])) {
			$post->otype = 'normal';
		}

		$model = new \frontend\models\OrderForm(['otype' => $post->otype]);
		if(($goods_info = $model->getGoodsInfo($post)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		
		// 如果是自己店铺的商品，则不能购买
		if (in_array(Yii::$app->user->id, $goods_info['storeIds'])) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('can_not_buy_yourself'));
		}

		// return $respond->output(true, null, ['Id' => 123]);
		
		// 获取订单模型
     	$order_type = Business::getInstance('order')->build($post->otype, $post);
		$result = $order_type->submit(array(
			'goods_info' => $goods_info
		));
		if(empty($result)) {
			return $respond->output(Respond::PARAMS_INVALID, $order_type->errors);
		}
			
		// 清理购物车商品等操作
		foreach($result as $store_id => $order_id) {
			$order_type->afterInsertOrder($order_id,  $store_id, $goods_info['orderList'][$store_id]);
		}
			
		// 有可能是支付多个订单
		$bizOrderId = implode(',', OrderModel::find()->select('order_sn')->where(['in', 'order_id', array_values($result)])->column());
		
		// 到收银台付款
		return $respond->output(true, null, ['bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_ORDER]);
	}
	
	/**
	 * 更新订单状态
	 * @api 接口访问地址: http://api.xxx.com/order/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id', 'status']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}
		
		$query = OrderModel::find()->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->andWhere(['order_id' => $post->order_id]);
		if(!($record = $query->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}
		
		// 只接受目标为：取消订单/发货/确认收货的状态变更
		if(!isset($post->status) || !in_array($post->status, [Def::ORDER_CANCELED,Def::ORDER_SHIPPED,Def::ORDER_FINISHED])) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('unsupport_status'));
		}

		$model = new \apiserver\models\OrderForm();
		
		// 取消订单
		if($post->status == Def::ORDER_CANCELED)
		{
			// 只有待付款的订单，才可以取消订单
			if(!in_array($record->status, [Def::ORDER_SUBMITTED,Def::ORDER_PENDING])) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('unsupport_status'));	
			}
			if(!$model->orderCancel($post, ArrayHelper::toArray($record))) {
				return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_fail'));	
			}
		}

		// 卖家发货
		if($post->status == Def::ORDER_SHIPPED) {
			if(!$model->orderShipped($post, ArrayHelper::toArray($record))) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
		}

		// 订单完成（买家确认收货）
		if($post->status == Def::ORDER_FINISHED) {
			if(!$model->orderFinished($post, ArrayHelper::toArray($record))) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);	
			}
		}

		return $respond->output(true);
	}
	
	/**
	 * 获取预提交订单数据集合
	 * @api 接口访问地址: http://api.xxx.com/order/build
	 */
    public function actionBuild()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id']);

		// 购物车/搭配购/拼团订单/社区团购订单
		if(!in_array($post->otype, ['normal', 'meal', 'teambuy', 'guidebuy'])) {
			$post->otype == 'normal';
		}
	
		return $this->build($respond, $post->otype, $post);
	}
	
	/**
	 * 获取订单商品数据
	 * @api 接口访问地址: http://api.xxx.com/order/goods
	 */
    public function actionGoods()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}
		
		$list = OrderGoodsModel::find()->select('rec_id,goods_id,spec_id,goods_name,goods_image,price,quantity,specification,comment,evaluation,o.order_id,o.order_sn,o.buyer_id,o.seller_id')
			->joinWith('order o', false)->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['o.order_id' => $post->order_id])->asArray()->all();
		
		foreach($list as $key => $value) {
			$list[$key]['subtotal'] = sprintf('%.2f', round($value['price'] * $value['quantity'], 2));
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}
		
		return $respond->output(true, null, ['list' => $list]);
	}
	
	/**
	 * 获取订单收货人数据
	 * @api 接口访问地址: http://api.xxx.com/order/extm
	 */
    public function actionExtm()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}
		
		$record = OrderExtmModel::find()->select('consignee,region_id,region_name,address,zipcode,phone_tel,phone_mob,o.order_id,o.order_sn,o.buyer_id,o.seller_id')
			->joinWith('order o', false)->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->andWhere(['o.order_id' => $post->order_id])->asArray()->one();

		if(!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}

		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取订单物流跟踪数据
	 * @api 接口访问地址: http://api.xxx.com/order/logistic
	 */
    public function actionLogistic()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}

		if(!($order = OrderModel::find()->select('order_id,order_sn,express_code,express_no,express_comkey,express_company,buyer_id,seller_id')->where(['order_id' => $post->order_id])->andWhere(['in', 'status', [Def::ORDER_SHIPPED,Def::ORDER_FINISHED]])->andWhere(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}
		
		// 每个订单发货用的快递插件会有不同
		$model = Plugin::getInstance('express')->build($order->express_code);
		if(!$model->isInstall()) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('no_such_express_plugin'));
		}
		
		if(($result = $model->submit($post, $order)) === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		
		return $respond->output(true, null, $result);
	}
	
	/**
	 * 从具体实例获取预支付订单数据
	 * @param string $otype = 'normal' 取购物车商品(可能包含多个店铺的商品)
	 * 				 $otype = 'meal' 取搭配套餐商品(只会有一个店铺的商品)
	 * 				 $otype = 'teambuy' 拼团商品(只会有一个店铺且一个商品)
	 * 				 $otype = 'guidebuy' 社区团购(可能包含多个店铺的商品)
	 */
	public function build($respond, $otype = 'normal', $post = null)
	{
		$model = new \frontend\models\OrderForm(['otype' => $otype]);
		if(($goods_info = $model->getGoodsInfo($post)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		
		// 如果是自己店铺的商品，则不能购买
		if (in_array(Yii::$app->user->id, $goods_info['storeIds'])) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('can_not_buy_yourself'));
		}
	
		// 获取订单模型
    	$order_type = Business::getInstance('order')->build($otype, $post);

		// 获取表单数据
		if(($form = $order_type->formData($goods_info)) === false) {
			return $respond->output(Respond::RECORD_NOTEXIST, $order_type->errors);
		}

		$list = array_merge(['list' => $goods_info], $form);
		return $respond->output(true, null, $list);
	}

	private function getOrderId($post)
	{
		if(isset($post->order_id)) {
			return $post->order_id;
		}

		if(isset($post->order_sn) && !empty($post->order_sn)) {
			return OrderModel::find()->select('order_id')->where(['order_sn' => $post->order_sn])->scalar();
		}

		return 0;
	}
}