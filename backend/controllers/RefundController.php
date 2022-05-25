<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;

use common\models\UserModel;
use common\models\IntegralModel;
use common\models\RefundModel;
use common\models\RefundMessageModel;
use common\models\DepositTradeModel;
use common\models\OrderModel;
use common\models\OrderExtmModel;
use common\models\DistributeModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id RefundController.php 2018.8.29 $
 * @author mosir
 */

class RefundController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['status_list'] = $this->getStatus();
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css'
			]);
			
			$this->params['page'] = Page::seo(['title' => Language::get('refund_list')]);
			return $this->render('../refund.index.html', $this->params);
		}
		else
		{
			$query = RefundModel::find()->alias('r')->select('r.refund_id,r.refund_sn,r.buyer_id,r.seller_id,r.total_fee,r.refund_total_fee,r.status,r.created,r.intervene,r.refund_reason, r.shipped,rb.username as buyer_name,s.store_name,s.store_id')
				->joinWith('refundBuyerInfo rb', false)
				->joinWith('store s', false);
			$query = $this->getConditions($post, $query)->orderBy(['refund_id' => SORT_DESC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['created'] = Timezone::localDate('Y-m-d H:i:s', $value['created']);
				$list[$key]['status'] = Language::get('REFUND_'.strtoupper($value['status']));
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	public function actionView()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$get->id || !($refund = RefundModel::find()->where(['refund_id' => $get->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_refund'));
		}
		
		if(!($tradeInfo = DepositTradeModel::find()->select('bizOrderId')->where(['tradeNo' => $refund['tradeNo']])->asArray()->one())) {
			return Message::warning(Language::get('no_such_trade'));
		}
		if(!($orderInfo = OrderModel::find()->with('orderGoods')->where(['order_sn' => $tradeInfo['bizOrderId']])->asArray()->one())) {
			return Message::warning(Language::get('no_such_order'));
		}
		
		if(!Yii::$app->request->isPost) 
		{
			$refund['shipped_label'] = Language::get('shipped_'.$refund['shipped']);
			$refund['status_label'] = Language::get('REFUND_'.strtoupper($refund['status']));
			
			// 取得列表数据
			$query = RefundMessageModel::find()->where(['refund_id' => $refund['refund_id']])->orderBy(['created' => SORT_DESC]);
			$page = Page::getPage($query->count(), 20);
			$this->params['refund'] = array_merge($refund, ['message' => $query->offset($page->offset)->limit($page->limit)->asArray()->all()]);
			$this->params['pagination'] = Page::formatPage($page);
			
			$orderInfo['shipping'] = OrderExtmModel::find()->where(['order_id' => $orderInfo['order_id']])->asArray()->one();
			$this->params['order'] = $orderInfo;
			
			$this->params['page'] = Page::seo(['title' => Language::get('refund_view')]);
			return $this->render('../refund.view.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			// 订单实际金额信息（考虑折扣，调价的情况）
			$realAmount = OrderModel::getRealAmount($orderInfo['order_id']);
		
			// 检查提交的数据
			if(!$this->checkData($post, $realAmount, $refund)) {
				return Message::warning($this->errors);
			}
			
			// 在此处理退款后的费用问题，将该商品的相关货款退还给买家(卖家)
			$refund_goods_fee    = $post->refund_goods_fee ? round($post->refund_goods_fee, 2) : 0;
			$refund_shipping_fee = $post->refund_shipping_fee ? round($post->refund_shipping_fee, 2) : 0;
			$refund_total_fee    = $refund_goods_fee + $refund_shipping_fee;
			
			$amount	= round(floatval($refund_total_fee), 2);
			$chajia	= $refund['total_fee'] - $amount;
			
			// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入 
			$depopay_type    = \common\library\Business::getInstance('depopay')->build('refund', $post);
			$result = $depopay_type->submit(array(
				'trade_info' => array('userid' => $orderInfo['seller_id'], 'party_id' => $orderInfo['buyer_id'], 'amount' => $amount),
				'extra_info' => $orderInfo + array('tradeNo' => $refund['tradeNo'], 'chajia' => $chajia, 'refund_id' => $get->id, 'operator' => 'admin')
			));
				
			if($result !== true) {
				return Message::warning($depopay_type->errors);
			}
			
			// 退款后（非全额退款），处理订单商品三级返佣 
			DistributeModel::distributeInvite($orderInfo);
				
			// 退款后的积分处理（积分返还，积分赠送）
			IntegralModel::returnIntegral($orderInfo);
			
			// 短信提醒：告知买家，客服已处理完毕
			Basewind::sendMailMsgNotify($orderInfo, array(),
				array(
					'sender'	=> 0, // 系统发送
					'receiver' => UserModel::find()->select('phone_mob')->where(['userid' => $orderInfo['buyer_id']])->scalar(),
					'key' => 'tobuyer_refund_agree_notify'
				)
			);
			
			return Message::display(Language::get('system_handle_refund_ok'));
		}
	}
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = RefundModel::find()->alias('r')->select('r.refund_id,r.refund_sn,r.buyer_id,r.seller_id,r.total_fee,r.refund_total_fee,r.status,r.created,r.intervene,r.refund_reason, r.shipped,rb.username as buyer_name,s.store_name,s.store_id')
			->joinWith('refundBuyerInfo rb', false)
			->joinWith('store s', false)
			->orderBy(['r.refund_id' => SORT_DESC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'r.refund_id', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}
		return \backend\models\RefundExportForm::download($query->asArray()->all());		
	}
	
	private function getStatus($status = null)
	{
		$result = array(
            'SUCCESS'				=> Language::get('REFUND_SUCCESS'),
            'CLOSED'				=> Language::get('REFUND_CLOSED'),
            'WAIT_SELLER_AGREE'		=> Language::get('REFUND_WAIT_SELLER_AGREE'),
            'SELLER_REFUSE_BUYER'	=> Language::get('REFUND_SELLER_REFUSE_BUYER'),
            'WAIT_SELLER_CONFIRM'	=> Language::get('REFUND_WAIT_SELLER_CONFIRM'),
        );
		if($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['refund_sn', 'buyer_name', 'store_name', 'status', 'add_time_from', 'add_time_to'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->refund_sn) {
			$query->andWhere(['like', 'refund_sn', $post->refund_sn]);
		}
		if($post->buyer_name) {
			$query->andWhere(['username' => $post->buyer_name]);
		}
		if($post->store_name) {
			$query->andWhere(['store_name' => $post->store_name]);
		}
		if($post->status) {
			$query->andWhere(['status' => $post->status]);
		}
		if($post->add_time_from) $post->add_time_from = Timezone::gmstr2time($post->add_time_from);
		if($post->add_time_to) $post->add_time_to = Timezone::gmstr2time_end($post->add_time_to);
		if($post->add_time_from && $post->add_time_to) {
			$query->andWhere(['and', ['>=', 'created', $post->add_time_from], ['<=', 'created', $post->add_time_to]]);
		}
		if($post->add_time_from && (!$post->add_time_to || ($post->add_time_to <= $post->add_time_from))) {
			$query->andWhere(['>=', 'created', $post->add_time_from]);
		}
		if(!$post->add_time_from && ($post->add_time_to && ($post->add_time_to > Timezone::gmtime()))) {
			$query->andWhere(['<=', 'created', $post->add_time_to]);
		}
	
		return $query;
	}
	
	private function checkData($post = null, $realAmount = array(), $refund = array())
	{
		list($realGoodsAmount, $realShippingFee, $realOrderAmount) = $realAmount;

		// 关闭或者是成功的退款，不能添加留言
		if(in_array($refund['status'], array('SUCCESS','CLOSED'))) {
			$this->errors = Language::get('add_refund_message_not_allow');
			return false;
		}	
		if(!$post->refund_goods_fee || floatval($post->refund_goods_fee < 0)) {
			$this->errors = Language::get('refund_fee_ge0');
			return false;
			
		} elseif(floatval($post->refund_goods_fee) > $realGoodsAmount) {
			$this->errors = Language::get('refund_fee_error');
			return false;
		}
		if(!$post->refund_shipping_fee && floatval($post->refund_shipping_fee < 0)) {
			$this->errors = Language::get('refund_shipping_fee_ge0');
			return false;
		}
		if(floatval($post->refund_shipping_fee) > $realShippingFee) {
			$this->errors = Language::get('refund_shipping_fee_error');
			return false;
		}
		
		return true;
	}
}
