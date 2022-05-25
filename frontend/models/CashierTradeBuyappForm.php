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
use common\models\AppbuylogModel;

use common\library\Language;
use common\library\Business;
use common\library\Def;

/**
 * @Id CashierTradeBuyappForm.php 2018.7.17 $
 * @author mosir
 */
class CashierTradeBuyappForm extends Model
{
	public $errors = null;
	
	public function getOrderId($post = null)
	{
		$orderId = array();
		
		// 商户订单号（合并付款时有多个，另：此实例不支持多个订单合并付款，此为兼容处理）
		if(!is_array($post->bizOrderId)) $post->bizOrderId = explode(',', $post->bizOrderId);
		if(count($post->bizOrderId) > 1) {
			$this->errors = Language::get('mergepay_disabled');
			return false;
		}
		
		foreach($post->bizOrderId as $bizOrderId)
		{
			// 此处不用判断此笔交易是否为当前用户发起
			if(($query = DepositTradeModel::find()->select('tradeNo')->where(['bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_BUYAPP])->one())) {
				$orderId[] = $query->tradeNo;
			} 
			else
			{
				// 交易号
				$tradeNo = DepositTradeModel::genTradeNo();
				
				// 取出购买信息
				if(!($appbuylog = AppbuylogModel::find()->where(['userid' => Yii::$app->user->id, 'orderId' => $bizOrderId])->asArray()->one())) {
					$this->errors = Language::get('no_such_order');
					return false;
				}
				
				// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
				$depopay_type = Business::getInstance('depopay')->build('buyapp', $post);
				$result = $depopay_type->submit(array(
					'trade_info' => array('userid' => $appbuylog['userid'], 'party_id' => 0, 'amount' => $appbuylog['amount']),
					'extra_info' => $appbuylog + array('tradeNo' => $tradeNo, 'bizOrderId' => $bizOrderId, 'bizIdentity' => Def::TRADE_BUYAPP, 'title' => AppbuylogModel::getSubjectOfPay($appbuylog))
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
