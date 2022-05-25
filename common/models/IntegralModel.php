<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;
use common\models\OrderIntegralModel;
use common\models\GoodsIntegralModel;
use common\models\OrderModel;
use common\models\StoreModel;

use common\library\Language;

/**
 * @Id IntegralModel.php 2018.3.22 $
 * @author mosir
 */

class IntegralModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%integral}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}

	/**
	 * 创建积分账户
	 */
	public static function createAccount($userid, $amount = 0) {
		$model = new IntegralModel();
		$model->userid = $userid;
		$model->amount = $amount;
		$model->save();
		return $model;
	}
	
	/* 插入积分记录，并返回最新的余额 */
	public static function updateIntegral($data = array())
	{
		extract($data);
		
		// 当积分开关未启用的情况下， 在数组中的积分变动类型继续执行
		$allow = array('return_integral', 'buying_has_integral', 'selling_has_integral');
		if(!in_array($type, $allow) && !IntegralSettingModel::getSysSetting('enabled')) {
			return false;
		}
		
		if(empty($amount) || empty($userid)) {
			return false;
		}
		
		$money = $amount;
		if($flow == 'minus') {
			$money = -$amount;
		}
			
		if(!parent::find()->where(['userid' => $userid])->exists()) {
			self::createAccount($userid, $amount);
			$balance = $amount;
		}
		else {
			parent::updateAllCounters(['amount' => $money], ['userid' => $userid]);
			$balance = parent::find()->select('amount')->where(['userid' => $userid])->scalar();
		}

		if(IntegralLogModel::addLog(array_merge($data, ['balance' => $balance]))) {
			return $balance;
		}
		return false;
	}
	
	/* 订单取消后，归还买家之前被预扣积分 */
	public static function returnIntegral($order_info = array())
	{
		if(!empty($order_info))
		{
			$orderIntegral = OrderIntegralModel::find()->select('frozen_integral')->where(['order_id' => $order_info['order_id']])->one();
			if($orderIntegral)
			{
				$data = array(
					'userid'  => $order_info['buyer_id'],
					'type'    => 'return_integral',
					'order_id'=> $order_info['order_id'],
					'order_sn'=> $order_info['order_sn'],
					'amount'  => $orderIntegral->frozen_integral,
					'flag'    => Language::get('return_integral_for_cancel_order')
				);
					
				self::updateIntegral($data);
				IntegralLogModel::updateAll(['state' => 'cancel'], ['order_id' => $order_info['order_id']]);
				OrderIntegralModel::deleteAll(['order_id' => $order_info['order_id']]);
			}
		}
	}
	
	/* 订单完成后分发积分。该操作可以不受是否开启积分的影响 */
	public static function distributeIntegral($order_info = array())
	{
		if(!empty($order_info))
		{
			$store = StoreModel::find()->select('sgrade')->where(['store_id' => $order_info['seller_id']])->one();
			
			// 订单实际金额信息（考虑折扣，调价的情况）
			list($realGoodsAmount) = OrderModel::getRealAmount($order_info['order_id']);
			
			// 订单完成给买家赠送积分（按实际支付的商品总额）
			$buy_has_integral = round($realGoodsAmount * IntegralSettingModel::getSysSetting(['buygoods', $store->sgrade]),2);
			if($buy_has_integral > 0)
			{
				self::updateIntegral(array(
					'userid'  => $order_info['buyer_id'],
					'type'    => 'buying_has_integral',
					'amount'  => $buy_has_integral,
					'order_id'=> $order_info['order_id'],
					'order_sn'=> $order_info['order_sn'],
					'flag'	  => sprintf(Language::get('buying_has_integral_logtext'), $order_info['order_sn']),
				));
			}
			
			// 买家使用抵扣的积分，给卖家
			if($query = OrderIntegralModel::find()->select('frozen_integral')->where(['order_id' => $order_info['order_id']])->one())
			{
				if($query->frozen_integral > 0)
				{
					// 把冻结的积分分发给商家
					self::updateIntegral(array(
						'userid'  => $order_info['seller_id'],
						'type'    => 'selling_has_integral',
						'amount'  => $query->frozen_integral,
						'order_id'=> $order_info['order_id'],
						'order_sn'=> $order_info['order_sn'],
						'flag'	  => sprintf(Language::get('selling_has_integral_logtext'), $order_info['order_sn']),
					));	
				}
				
				OrderIntegralModel::deleteAll(['order_id' => $order_info['order_id']]);
				
				// 把冻结的记录状态改为完成
				IntegralLogModel::updateAll(['state' => 'finished'], ['order_id' => $order_info['order_id']]); 
			}
		}
	}
	
	/* 积分变动的状态，完成，取消，冻结 */
	public static function getStatusLabel($string = '')
	{
		$status = array(
			'finished' => 'integral_finished',
			'frozen'   => 'integral_frozen',
			'cancel'   => 'integral_cancel'
		);
		return isset($status[$string]) ? Language::get($status[$string]) : '';
	}
	
	/* 订单页，获取积分信息，以便做验证 */
	public static function getIntegralByOrders(array $goodsList)
	{
		$maxPoints = $getPoints = $exchange_rate = 0;
		$orderIntegral = array();
		
		if(IntegralSettingModel::getSysSetting('enabled'))
		{
			// 积分兑换比率
			if(!($exchange_rate = IntegralSettingModel::getSysSetting('rate'))){
				$exchange_rate = 0;
			}
			
			$integralRate = array();
			foreach($goodsList as $goods)
			{
				// 获取店铺等级对应的积分比率
				if(!isset($integralRate[$goods['store_id']])) {
					$store = StoreModel::find()->select('sgrade')->where(['store_id' => $goods['store_id']])->one();
					$integralRate[$goods['store_id']] = IntegralSettingModel::getSysSetting(['buygoods', $store->sgrade]);
				}
				
				// （计算获得赠送的积分）如果店铺所处的等级的购物赠送积分比率大于零
				if($integralRate[$goods['store_id']] > 0)
				{
					$sgrade_integral = $integralRate[$goods['store_id']];
					$getPoints += $goods['price'] * $goods['quantity'] * $sgrade_integral;
				}
				
				// （计算可最多使用多少积分抵扣） 如果积分兑换比率大于零
				if($exchange_rate > 0)
				{
					$goods_integral = GoodsIntegralModel::find()->select('max_exchange')->where(['goods_id' => $goods['goods_id']])->one();
						
					// 如果单个商品的最大积分抵扣小于或等于单个商品的价格，则是合理的，否则，仅取能抵扣完商品价格的积分值
					$max_exchange_price = round($goods_integral->max_exchange * $exchange_rate, 2);
					if($max_exchange_price > $goods['price']) {
						$max_exchange_price = $goods['price'];	
					}
					
					// 购物车中每个商品可使用的最大抵扣积分值
					$goodsMaxPoints = ($max_exchange_price / $exchange_rate) * $goods['quantity'];
					
					// 每个订单最多可使用的最大抵扣积分值
					if(!isset($orderIntegral[$goods['store_id']])) $orderIntegral[$goods['store_id']] = 0;
					$orderIntegral[$goods['store_id']] += $goodsMaxPoints;
					
					$maxPoints += $goodsMaxPoints;
				}
			}
		}
		
		// 获取用户拥有的积分
		if(($integral = parent::find()->select('amount')->where(['userid' => Yii::$app->user->id])->one())) {
			$userIntegral = $integral->amount;
		} else{
			$userIntegral = 0;
		}
		
		$maxPoints = round($maxPoints, 2);
		$getPoints = round($getPoints, 2);
		
		if($maxPoints > $userIntegral) {
			
			foreach($orderIntegral as $key => $val)
			{
				$orderIntegral[$key] = round($val * ($userIntegral / $maxPoints), 2);
			}
			$maxPoints = $userIntegral;
		}
		
		$result = array(
			'maxPoints' => $maxPoints, 'userIntegral' => $userIntegral, 'getPoints' => $getPoints, 'rate' => $exchange_rate,
			'orderIntegral' => array('totalPoints' => $maxPoints, 'items' => $orderIntegral));
		
		return $result;	
	}
}
