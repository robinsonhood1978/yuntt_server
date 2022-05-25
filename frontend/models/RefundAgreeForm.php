<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\IntegralModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id RefundAgreeForm.php 2018.10.19 $
 * @author mosir
 */
class RefundAgreeForm extends Model
{
	public $errors = null;
	
	/**
	 * 同意退款申请
	 * @api API接口用到该数据
	 */
	public function submit($post = null, $sendNotify = true)
	{
		if(!$post->id || !($refund = RefundModel::find()->alias('r')->select('r.*,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,rb.phone_mob')->joinWith('depositTrade dt', false)->joinWith('refundBuyerInfo rb', false)->where(['refund_id' => $post->id, 'r.seller_id' => Yii::$app->user->id])->andWhere(['not in', 'r.status', ['SUCCESS','CLOSED']])->asArray()->one())) {
			$this->errors = Language::get('agree_disallow');
			return false;
		}
		
		if(($refund['bizIdentity'] == Def::TRADE_ORDER) && $refund['bizOrderId']) {
			$order = OrderModel::find()->where(['order_sn' => $refund['bizOrderId']])->asArray()->one();
		}
		
		// 目前只考虑普通购物订单的退款，如果需要考虑其他业务的退款，请再这里拓展
		if(!$order) {
			$this->errors = Language::get('no_such_order');
			return false;
		}
		
		$amount	= $refund['refund_total_fee'];
		$chajia	= round($refund['total_fee'] - $amount, 2);
		
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入 
		$depopay_type = \common\library\Business::getInstance('depopay')->build('refund', $post);
		$result = $depopay_type->submit(array(
			'trade_info' => array('userid' => $order['seller_id'], 'party_id' => $order['buyer_id'], 'amount' => $amount),
			'extra_info' => $order + array('tradeNo' => $refund['tradeNo'], 'chajia' => $chajia, 'refund_id' => $post->id, 'operator' => 'seller')
		));
			
		if($result !== true) {
			$this->errors = $depopay_type->errors;
			return false;
		}
			
		// 退款后的积分处理（积分返还，积分赠送）
		IntegralModel::returnIntegral($order);
		
		if($sendNotify === true) {
			// 短信提醒：卖家同意退款，通知买家
			Basewind::sendMailMsgNotify($order, array(),
				array(
					'receiver'  => $refund['phone_mob'],
					'key' 		=> 'tobuyer_refund_agree_notify',
				)
			);
		}
		
		return true;
	}
}