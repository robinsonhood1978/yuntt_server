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

use common\library\Language;
use common\library\Business;
use common\library\Def;

/**
 * @Id CashierTradeOrderForm.php 2018.7.17 $
 * @author mosir
 */
class CashierTradeOrderForm extends Model
{
	public $errors = null;
	
	public function getOrderId($post = null)
	{
		$orderId = array();
		
		// 商户订单号（合并付款时有多个）
		if(!is_array($post->bizOrderId)) $post->bizOrderId = explode(',', $post->bizOrderId);
		
		foreach($post->bizOrderId as $bizOrderId)
		{
			// 此处不用判断此笔交易是否为当前用户发起
			if(($query = DepositTradeModel::find()->select('tradeNo')->where(['bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_ORDER])->one())) {
				$orderId[] = $query->tradeNo;
			} 
			else
			{
				// 交易号
				$tradeNo = DepositTradeModel::genTradeNo();
	
				if(!($order = OrderModel::find()->where(['order_sn' => $bizOrderId, 'buyer_id' => Yii::$app->user->id])->asArray()->one())) {
					$this->errors = Language::get('no_such_order');
					return false;
				}
					
				// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
				$depopay_type = Business::getInstance('depopay')->build('buygoods', $post);
				$result = $depopay_type->submit(array(
					'trade_info' => array('userid' => $order['buyer_id'], 'party_id' => $order['seller_id'], 'amount' => $order['order_amount']),
					'extra_info' => $order + array('tradeNo' => $tradeNo, 'bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_ORDER, 'title' => OrderModel::getSubjectOfPay($order['order_id']))
				));
				
				if(!$result) {
					$this->errors = $depopay_type->errors;
					return false;
				}
				$orderId[] = $tradeNo;
			}
		}
		return $orderId;
	} 
}
