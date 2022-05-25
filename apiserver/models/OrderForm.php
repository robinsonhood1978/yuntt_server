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

use common\models\OrderModel;
use common\models\OrderLogModel;
use common\models\RefundModel;
use common\models\DepositTradeModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

use apiserver\library\Formatter;

/**
 * @Id OrderForm.php 2019.1.3 $
 * @author yxyc
 */
class OrderForm extends Model
{
	public $enter = 'buyer';
	public $errors = null;
	
	/**
	 * 获取订单数据
	 */
	public function formData($post = null)
	{
		$query = OrderModel::find()->alias('o')->select('o.order_id,o.order_sn,o.gtype,o.otype,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name,o.status,o.evaluation_status,o.payment_code,o.payment_name,o.express_no,o.express_comkey,o.express_company,o.goods_amount,o.order_amount,o.postscript,o.memo,o.add_time,o.pay_time,o.ship_time,o.finished_time,o.evaluation_time,o.guider_id,oe.shipping_fee')
			->joinWith('orderExtm oe', false)
			->where(['>', 'o.order_id', 0])
			->orderBy(['o.order_id' => SORT_DESC]);
		
		if($post->otype) {
			$query->andWhere(['otype' => $post->otype]);
		}

		// 卖家获取订单管理数据
		if($this->enter == 'seller') {
			$query->andWhere(['o.seller_id' => Yii::$app->user->id]);
			$query->addSelect('oe.consignee,oe.phone_mob');
		}
		// 团长获取订单管理数据
		elseif($this->enter == 'guider') {
			$query->andWhere(['o.guider_id' => Yii::$app->user->id]);
			$query->addSelect('oe.consignee,oe.phone_mob');
		}
		// 买家获取我的订单数据
		else {
			$query->andWhere(['o.buyer_id' => Yii::$app->user->id]);
		}
		// 根据订单状态筛选订单
		if(isset($post->type) && ($status = Def::getOrderStatusTranslator($post->type)) > -1) {
			$query->andWhere(['o.status' => $status]);
		}
		// 根据评价状态筛选
		if(isset($post->evaluation_status)) {
			$query->andWhere(['evaluation_status' => intval($post->evaluation_status)]);
		}

		// 获取指定的时间段的订单
		if($post->begin) {
			$query->andWhere(['>=', 'o.pay_time', Timezone::gmstr2time($post->begin)]);
		}
		if($post->end) {
			$query->andWhere(['<=', 'o.pay_time', Timezone::gmstr2time($post->end)]);
		}

		// 是否获取订单商品数据
		if(isset($post->queryitem) && ($post->queryitem === true)) {
			$query->with(['orderGoods' => function($model){
				$model->select('rec_id,spec_id,order_id,goods_id,goods_name,goods_image,specification,price,quantity');
			}]);
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key] = $this->formatDate($value);
			
			if(($trade = DepositTradeModel::find()->select('tradeNo,bizOrderId,bizIdentity')->where(['bizOrderId' => $value['order_sn'], 'bizIdentity' => Def::TRADE_ORDER])->asArray()->one())) {
				$list[$key] = array_merge($list[$key], $trade);
			}

			// 查看是否有退款
			if(($refund = $this->getOrderRefund($value))) {
				$list[$key] = array_merge($list[$key], $refund);
			}
			if(isset($value['orderGoods'])) {
				$list[$key]['items'] = $this->formatImage($value['orderGoods']);
				unset($list[$key]['orderGoods']);
			}

			// 对卖家订单和团长订单返回收（取）货人信息
			if(in_array($this->enter, ['seller', 'guider'])) {
				$shipping = ['name' => $value['consignee'], 'phone_mob' => $value['phone_mob']];
				unset($list[$key]['phone_mob']);
				$list[$key]['consignee'] = $shipping;
			}
		}
		
		return array($list, $page);	
	}

	/**
	 * 取消订单
	 * @desc 适用买家或卖家取消订单
	 */
	public function orderCancel($post, $orderInfo = array())
	{
		$orders = array($orderInfo['order_id'] => $orderInfo);
		if($orderInfo['buyer_id'] == Yii::$app->user->id) {
			$model = new \frontend\models\Buyer_orderCancelForm();
		} else {
			$model = new \frontend\models\Seller_orderCancelForm();
		}

		return $model->submit($post, $orders, false);
	}

	/**
	 * 卖家发货
	 */
	public function orderShipped($post, $orderInfo = array())
	{
		if($orderInfo['seller_id'] != Yii::$app->user->id) {
			$this->errors = Language::get('handle_invalid');
			return false;
		}
		$model = new \frontend\models\Seller_orderShippedForm();
		if(!$model->submit($post, $orderInfo, false)) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}

	/**
	 * 团长通知取货（针对社区团购订单）
	 */
	public function orderDelivered($post, $orderInfo = array())
	{
		// 交易信息 
		if(!($tradeInfo = DepositTradeModel::find()->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $orderInfo['order_sn']])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
		 	return false;
		}
		OrderModel::updateAll(['status' => Def::ORDER_DELIVERED, 'ship_time' => Timezone::gmtime()], ['order_id' => $orderInfo['order_id'], 'guider_id' => Yii::$app->user->id]);
		
		// 记录订单操作日志
		$model = new OrderLogModel();
		$model->order_id = $orderInfo['order_id'];
		$model->operator = Language::get('system');
		$model->order_status = Def::getOrderStatus($orderInfo['status']);
		$model->changed_status = Def::getOrderStatus(Def::ORDER_DELIVERED);
		$model->remark = '';
		$model->log_time = Timezone::gmtime();
		$model->save();
		
		return true;
	}

	/**
	 * 买家确认收货
	 */
	public function orderFinished($post, $orderInfo = array())
	{
		// 交易信息 
		if(!($tradeInfo = DepositTradeModel::find()->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $orderInfo['order_sn'], 'buyer_id' => Yii::$app->user->id])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
		 	return false;
		}
		 
		$model = new \frontend\models\Buyer_orderConfirmForm();
		if(!$model->submit($post, $orderInfo, $tradeInfo, false)) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
	
	/**
	 * 格式化时间
	 */
	public function formatDate($record)
	{
		$fields = ['add_time', 'pay_time', 'ship_time', 'finished_time', 'evaluation_time'];
		foreach($fields as $field) {
			isset($record[$field]) && $record[$field] = Timezone::localDate('Y-m-d H:i:s', $record[$field]);
		}
		return $record;
	}
	
	/**
	 * 格式化路径
	 */
	public function formatImage($list)
	{
		foreach($list as $key => $value) {
			if(isset($list[$key]['goods_image'])) {
				$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			}
		}
		return $list;
	}
	
	/**
	 * 获取订单是否有退款
	 */
	private function getOrderRefund($order = [])
	{
		// 是否申请过退款
		$tradeNo = DepositTradeModel::find()->select('tradeNo')->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order['order_sn']])->scalar();
			
		if(!empty($tradeNo) && ($refund = RefundModel::find()->select('refund_id,refund_sn,status as refund_status')->where(['tradeNo' => $tradeNo])->asArray()->one())) {
			return $refund;
		}

		return false;
	}
}
