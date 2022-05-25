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
use common\models\DepositTradeModel;
use common\models\RefundModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id Seller_orderViewForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderViewForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		if(!$post->order_id || !($orderInfo = OrderModel::find()->alias('o')->select('o.order_id,o.buyer_id,o.seller_id,o.order_amount,o.discount,o.payment_code,o.payment_name,o.pay_message,o.pay_time,o.ship_time,o.finished_time,o.express_no,o.postscript,o.status,o.order_sn,o.add_time as order_add_time, s.store_name,s.region_name,s.address,s.im_qq')->joinWith('store s', false)->joinWith('orderExtm')->with('orderGoods')->with('orderLog')->where(['o.order_id' => $post->order_id, 'seller_id' => $this->store_id])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}

		// for PC
		if(Basewind::getCurrentApp() == 'pc') {
			$orderInfo['buyer_info'] = UserModel::find()->select('username,phone_mob,phone_tel,email')->where(['userid' => $orderInfo['buyer_id']])->asArray()->one();	
		}
		
		return $orderInfo;
	}
	
	/* 是否申请过退款 - 手机端才需要 */
	public function getOrderRefund($orderInfo = array())
	{
		$tradeNo = DepositTradeModel::find()->select('tradeNo')->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $orderInfo['order_sn']])->scalar();
		if(!empty($tradeNo) && ($refund = RefundModel::find()->select('refund_id,status')->where(['tradeNo' => $tradeNo])->one())) {
			if(in_array($refund->status, array('SUCCESS'))) {
				$orderInfo['refund_status_label'] = Language::get('refund_success');
			} elseif(!in_array($refund->status, array('CLOSED'))) {
				$orderInfo['refund_status_label'] = Language::get('refund_waiting');
			}
			$orderInfo['refund_id'] = $refund->refund_id;
		}
		return $orderInfo;
	}
}
