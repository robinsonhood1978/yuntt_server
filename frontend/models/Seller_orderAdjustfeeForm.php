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
use common\models\UserModel;
use common\models\OrderLogModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;;

/**
 * @Id Seller_orderAdjustfeeForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderAdjustfeeForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		if(!$post->order_id || !($orderInfo = OrderModel::find()->alias('o')->select('o.order_id,order_sn,buyer_name,order_amount,goods_amount,discount,adjust_amount,shipping_fee')->joinWith('orderExtm ex',false)->where(['o.order_id' => $post->order_id, 'seller_id' => $this->store_id])->andWhere(['in', 'status', [Def::ORDER_SUBMITTED, Def::ORDER_PENDING]])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}
		return $orderInfo;
	}
	
	public function submit($post = null, $orderInfo = array())
	{
		// 订单实际总金额
		$post->order_amount = $post->order_amount ? abs(floatval($post->order_amount)) : $orderInfo['order_amount'];
		if($post->order_amount <= 0) {
			$this->errors = Language::get('invalid_fee');
			return false;
		}
			
		$model = OrderModel::findOne($orderInfo['order_id']);
		$model->order_amount = $post->order_amount;
		$model->adjust_amount = $post->order_amount - ($orderInfo['goods_amount'] + $orderInfo['shipping_fee'] - $orderInfo['discount']);
		$model->pay_alter = 1;
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		DepositTradeModel::updateAll(['amount' => $post->order_amount, 'pay_alter' => 1], ['bizOrderId' => $orderInfo['order_sn']]);

		$model = new OrderLogModel();
		$model->order_id = $orderInfo['order_id'];
		$model->operator = addslashes(Yii::$app->user->identity->username);
		$model->order_status = Def::getOrderStatus($orderInfo['status']);
		$model->changed_status = Def::getOrderStatus($orderInfo['status']);
		$model->remark = $post->remark;
		$model->log_time = Timezone::gmtime();
		$model->save();
			
        // 发送给买家邮件通知，订单金额已改变，等待付款
		$mailer = Basewind::getMailer('tobuyer_adjust_fee_notify', ['order' => $orderInfo, 'reason' => $model->remark]);
		if($mailer && ($toEmail = UserModel::find()->select('email')->where(['userid' => $orderInfo['buyer_id']])->scalar())) {
			$mailer->setTo($toEmail)->send();
		}
		
		return true;
	}
}
