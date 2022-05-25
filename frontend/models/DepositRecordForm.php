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

use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\OrderModel;
use common\models\RefundModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id DepositRecordForm.php 2018.9.27 $
 * @author mosir
 */
class DepositRecordForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		if(!$post->tradeNo || !($record = DepositTradeModel::find()->where(['tradeNo' => $post->tradeNo])->asArray()->one())) {
			$this->errors = Language::get('no_records');
			return false;
		}
		
		//  这笔交易既不是买家，也不是卖家，则认为当前用户跟这笔交易无关，无法访问交易信息
		if(!in_array(Yii::$app->user->id, array($record['buyer_id'], $record['seller_id']))){
			$this->errors = Language::get('no_priv_view_record');
			return false;
		}
		
		// 交易的对方
		$record['partyInfo'] = DepositTradeModel::getPartyInfoByRecord(Yii::$app->user->id, $record);
		
		if($extraInfo = DepositRecordModel::find()->where(["userid" => Yii::$app->user->id, 'tradeNo' => $post->tradeNo])->asArray()->one()){
			$record = array_merge($record, $extraInfo);
		}
		$record['status_label'] = Language::get('TRADE_'.strtoupper($record['status']));
		
		// 如果是商品订单
		if(in_array($record['bizIdentity'], array(Def::TRADE_ORDER)))
		{
			$query = OrderModel::find()->alias('o')
				->select('o.order_id,o.buyer_id,o.seller_id,o.order_amount,o.payment_name,oe.shipping_fee,oe.shipping_name,s.store_name')
				->where(['order_sn' => $record['bizOrderId']])
				->joinWith('store s', false)
				->joinWith('orderExtm oe', false);

			if(Basewind::getCurrentApp() == 'pc') {
				$query->with('orderGoods');
			}
			
			$order = $query->asArray()->one();
			if($order) 
			{
				// 查询交易是否有退款
				list($refund, $status_label) = RefundModel::checkTradeHasRefund($record);
				if($refund) {
					$record['refundInfo']   = $refund;
					$record['status_label'] = $status_label;
					
					// 扣除退款金额后的交易应付金额
					$record['amount'] 		= round($refund['total_fee'] - $refund['refund_total_fee'], 2);
					$record['tradeAmount'] 	= $refund['total_fee'];
				}
			}
			$record['orderInfo'] = $order;
		}
		return $record;
	}
}
