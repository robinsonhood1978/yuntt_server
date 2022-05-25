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

use common\models\OrderModel;
use common\models\DepositAccountModel;
use common\models\UserModel;
use common\models\AppbuylogModel;

use common\library\Def;
use common\library\Language;
use common\library\Timezone;

/**
 * @Id DepositTradeModel.php 2018.4.2 $
 * @author mosir
 */

class DepositTradeModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deposit_trade}}';
    }
	
	// 关联表
	public function getDepositAccountBuyer()
	{
		return parent::hasOne(DepositAccountModel::className(), ['userid' => 'buyer_id']);
	}
	// 关联表
	public function getDepositAccountParty()
	{
		return parent::hasOne(DepositAccountModel::className(), ['userid' => 'party_id']);
	}
	
	// 关联表
	public function getDepositWithdraw()
	{
		return parent::hasOne(DepositWithdrawModel::className(), ['orderId' => 'bizOrderId']);
	}

	// 关联表
	public function getRefund()
	{
		return parent::hasOne(RefundModel::className(), ['tradeNo' => 'tradeNo', 'buyer_id' => 'buyer_id']);
	}
	
	/**
     *  create unique tradeNo
     *  @return    string
     */
    public static function genTradeNo( $length = 0, $field = 'tradeNo')
    {
        // 选择一个随机的方案
        mt_srand((double) microtime() * 1000000);
		
		if($length > 0) {
			$tradeNo = self::makeChar( $length );
		} else {
        	$tradeNo = Timezone::localDate('YmdHis', Timezone::gmtime()) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT).mt_rand(1000, 9999);
		}
		$field = in_array($field, ['tradeNo', 'bizOrderId', 'payTradeNo']) ? $field : 'tradeNo';
        if (!parent::find()->where([$field => $tradeNo])->exists()) {
            return $tradeNo;
        }
        // 如果有重复的，则重新生成
        return self::genTradeNo( $length, $field);
    }
	
	/* 生成对应支付接口的商户交易号，也即跟系统内的外部交易号对应 */
	public static function genPayTradeNo($orderInfo = array(), $length = 0)
	{
		$payTradeNo = NULL;
		
		if(empty($orderInfo)) {
			
			$payTradeNo = self::genTradeNo( $length, 'payTradeNo');
		}
		else
		{
			// 如果本次所有交易中，都有商户交易号且一样，并且支付方式没有变更，也不存在未在本次支付的交易有相同的商户交易号，则取现在的商户交易号，其他情况下都重新生成
			$genPayTradeNo = false;
			$tempPayTradeNo= array();
			foreach($orderInfo['tradeList'] as $tradeInfo)
			{
				// 商户交易号为空或者支付已变更
				if(!$tradeInfo['payTradeNo'] || $tradeInfo['pay_alter']) {
					$genPayTradeNo = true;
					break;
				}
				$tempPayTradeNo[] = $tradeInfo['payTradeNo'];
				
				// 去掉重复值
				$tempPayTradeNo = array_unique($tempPayTradeNo);
				
				// 说明有多个不同商户交易号
				if(count($tempPayTradeNo) > 1) {
					$genPayTradeNo = true;
					break;
				}
			}
			
			// 系统中还存在相同的商户交易号，但未在本次付款中
			if(empty($tempPayTradeNo)) $samePayTradeNo = array();
			else $samePayTradeNo = parent::find()->where(['payTradeNo' => current($tempPayTradeNo)])->indexBy('trade_id')->asArray()->all();
			if(count($samePayTradeNo) != count($orderInfo['tradeList'])) {
				$genPayTradeNo = true;
				
				// 为避免交易影响，置空商户交易号（这样做的目的是，如果不置空，那么当买家又发起单交易支付时， 会继续使用原商户交易号，导致支付的金额不匹配
				$diff = array_diff(array_keys($samePayTradeNo), array_keys($orderInfo['tradeList']));
				if(parent::updateAll(['payTradeNo' => ''], ['in', 'trade_id', $diff]))
				{
					// 创建一个该笔商户订单号的副本，以便支付通知返回后找不到交易交易记录，无法处理已支付的资金退回问题
					$tempTradeInfo = json_encode($samePayTradeNo);
					$path = Yii::getAlias('@webroot') . '/data/files/mall/tradelog';
					@mkdir($path, 0777, true);
					file_put_contents($path . '/' . md5(current($tempPayTradeNo)).'.log', $tempTradeInfo, LOCK_EX);
				}
			}
			
			if($genPayTradeNo === false) {
				$payTradeNo = current($tempPayTradeNo);
			}
			else {
				$payTradeNo = self::genTradeNo( $length, 'payTradeNo');
			}
		}
		
		return $payTradeNo;
	}
	
	/* 生成指定长度的随机字符串 */
	private static function makeChar( $length = 8 )
	{  
		// 密码字符集，可任意添加你需要的字符  
		$chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');  

		$str = '';
		for($i = 0; $i < $length; $i++){  
   			$str .= $chars[array_rand($chars)];
		}
		if(substr( $str, 0, 1 ) == '0') {
			$str = self::makeChar( $length );
		}

		return $str;
	}
	
	/* 验证合并付款中交易信息有效性，正确则返回交易数组等数据 */
	public static function checkAndGetTradeInfo($orderId = 0, $userid = 0)
	{
		$result 	= array();
		$errorMsg 	= false;
			
		if(!$orderId) {
			$errorMsg = Language::get('no_such_order');
			return array($errorMsg, $result);
		}
	
		// 如果为多个TradeNo， 则说明是合并付款	
		$tradeList = parent::find()->where(['in', 'tradeNo', explode(',', $orderId)])->indexBy('trade_id')->asArray()->all();
			
		if(empty($tradeList)) {
			$errorMsg = Language::get('no_such_order');
			return array($errorMsg, $result);
		}
	
		foreach($tradeList as $tradeInfo)
		{
			if(($tradeInfo['buyer_id'] != $userid)){
				$errorMsg = sprintf(Language::get('not_pay_for_not_yourself'), $tradeInfo['tradeNo']);
				break;
			}
			elseif($tradeInfo['status'] != 'PENDING')
			{
				$errorMsg = sprintf(Language::get('not_pay_for_not_pending_of_trade'), $tradeInfo['tradeNo']);
				break;
			}
						
			// 如果是普通购物订单
			if(in_array($tradeInfo['bizIdentity'], array(Def::TRADE_ORDER))) 
			{
				if(!($order_info = OrderModel::find()->where(['order_sn' => $tradeInfo['bizOrderId']])->asArray()->one())) {
					$errorMsg = sprintf(Language::get('not_pay_for_no_such_order'), $tradeInfo['bizOrderId']);
					break;
				}
				elseif($order_info['buyer_id'] != $userid) {
					$errorMsg = sprintf(Language::get('not_pay_for_not_yourself'), $tradeInfo['bizOrderId']);
					break;
				}
				elseif($order_info['status'] != Def::ORDER_PENDING) {
					$errorMsg = sprintf(Language::get('not_pay_for_not_pending_of_order'), $tradeInfo['bizOrderId']);
					break;
				}
				elseif($tradeInfo['amount'] != $order_info['order_amount']) {
					$errorMsg = sprintf(Language::get('not_pay_for_order_amount_invalid'), $tradeInfo['bizOrderId']);
					break;
				}
				$tradeInfo['seller'] = $order_info['seller_name'];
				$result['orderList'][$order_info['order_id']] = $order_info;
			}
					
			// 如果是购买APP应用服务的订单（暂不考虑合并付款的的情况）
			elseif(in_array($tradeInfo['bizIdentity'], array(Def::TRADE_BUYAPP))) {
				$tradeInfo['seller'] = Language::get('platform_appmarket');
			}
					
			$tradeInfo['name'] = substr($tradeInfo['title'], 9);
			$result['tradeList'][$tradeInfo['trade_id']] = $tradeInfo;
						
			// 计算合并付款的总金额
			if(!isset($result['amount'])) $result['amount'] = 0;
			$result['amount'] += $tradeInfo['amount'];
			$result['payType'] = $tradeInfo['payType'];
			
			// 获取商务业务代码
			$result['bizIdentity'] = $tradeInfo['bizIdentity'];
		}
					
		if($errorMsg === false)
		{
			// 说明是合并付款
			if(count($result['tradeList']) > 1)
			{
				$result['title'] = sprintf(Language::get('mergepay_num_order'), count($result['tradeList']));
				$result['mergePay'] = true;
			}
			else
			{
				$firstTradeInfo 	= current($result['tradeList']);
				$result['title'] 	= addslashes($firstTradeInfo['title']);
			}
		}
		return array($errorMsg, $result);
	}
	
	/* 获取交易数据给网关支付后的业务处理 */
	public static function getTradeInfoForNotify($payTradeNo = 0)
	{
		$result	= array();
		
		// 当支付变更后，置空受影响的商户交易号后，这里获取到的tradeList，要么就是空，要么就是全部待付款的交易记录
		$tradeList 	= parent::find()->where(['payTradeNo' => $payTradeNo])->indexBy('trade_id')->asArray()->all();
		
		if(empty($tradeList)) 
		{
			/* 如果没找到交易记录，那么说明交易变更了，从交易日志中获取交易数据，待异步通知验证通过后，充值支付的金额到余额账户
			 * 情况一: 还有待付款的交易，继续完成交易流程
			 * 情况二: 没有待付款的交易，仅做充值就完结
			 */
			$tradelog = Yii::getAlias('@webroot') . '/data/files/mall/tradelog/'.md5($payTradeNo).'.log';
			if(file_exists($tradelog))
			{
				$tradeList = json_decode(file_get_contents($tradelog), true);
				if(!empty($tradeList)) {
					$returnMoney = true; // 资金退回标记
					foreach($tradeList as $tradeInfo) {
						if($tradeInfo['status'] == 'PENDING') 
							$returnMoney = false;
							break;
					}
					$result['RETURN_MONEY'] = $returnMoney;
					$result['tradelogfile'] = $tradelog;
				}
			}	
		}
		
		if($tradeList)
		{
			$firstTradeInfo = current($tradeList);
			
			// 获取基本参数，给网关通知验证调用（必须放循环前面以便能正确获取到值）
			$result['buyer_id']		= $firstTradeInfo['buyer_id'];
			$result['payTradeNo']	= $firstTradeInfo['payTradeNo'];
			$result['bizIdentity'] 	= $firstTradeInfo['bizIdentity'];
			$result['payment_code']	= $firstTradeInfo['payment_code'];
			$result['title'] 		= addslashes($firstTradeInfo['title']);
			
			$result['amount'] 	= 0;
			foreach($tradeList as $tradeInfo)
			{
				if($tradeInfo['status'] == 'PENDING')
				{
					if(in_array($tradeInfo['bizIdentity'], array(Def::TRADE_ORDER))) 
					{
						$order_info = OrderModel::find()->where(['order_sn' => $tradeInfo['bizOrderId'], 'status' => Def::ORDER_PENDING])->asArray()->one();
					}
					
					// 如果是购买APP订单
					elseif(in_array($tradeInfo['bizIdentity'], array(Def::TRADE_BUYAPP))) 
					{
						$order_info = AppbuylogModel::find()->where(['orderId' => $tradeInfo['bizOrderId'], 'status' => Def::ORDER_PENDING])->asArray()->one();
					}
					
					// 每笔交易对应的订单信息
					if($order_info){
						$tradeInfo['seller'] 		= $order_info['seller_name'];
						$tradeInfo['order_info']	= $order_info;
					}
						
					$result['tradeList'][$tradeInfo['trade_id']] = $tradeInfo;
				}
				
				$result['amount'] += $tradeInfo['amount'];	
			}
			// 说明是合并付款
			if(count($result['tradeList']) > 1)
			{
				$result['title'] = sprintf(Language::get('mergepay_num_order'), count($result['tradeList']));
				$result['mergePay'] = true;
			}
		}
		return $result;
	}
	
	/* 获取交易的对方信息（这里获取的是资金账户的信息） */
	public static function getPartyInfoByRecord($userid, $record)
	{
		$partyInfo = array();
		
		// 交易的对方
		$party_id = ($record['buyer_id'] == $userid) ? $record['seller_id'] : $record['buyer_id'];
			
		// 找出对方信息
		if($party_id) {
			$partyInfo = DepositAccountModel::find()->select('real_name as name, account')->where(['userid' => $party_id])->asArray()->one();
			empty($partyInfo['name']) && $partyInfo['name'] = $partyInfo['account'];
			
			$partyInfo['portrait'] = UserModel::find()->select('portrait')->where(['userid' => $party_id])->scalar();
			if(empty($partyInfo['portrait'])) {
				$partyInfo['portrait'] = Yii::$app->params['default_user_portrait'];
			}
		}
		else {
			if(in_array($record['tradeCat'], array('WITHDRAW', 'RECHARGE')) && $record['fundchannel']) {
				$partyInfo = array('name' => $record['fundchannel']);
			}
			elseif(in_array($record['bizIdentity'], array(Def::TRADE_BUYAPP))) { // 此处使用商户业务类型来判断并不合适，以后再优化
				$partyInfo = array('name' => Language::get('platform_appmarket'));
			}
			else $partyInfo = array('name' => Language::get('platform'));
		}
		return $partyInfo;
	}
	
	/* 更新每笔交易的付款方式 */
	public static function updateTradePayment(&$orderInfo = array(), $payment_code = '')
	{
		foreach($orderInfo['tradeList'] as $key => $tradeInfo)
		{
			$edit_data = array('payment_code' => $payment_code);
			
			// 如果支付方式表更了
			if($tradeInfo['payment_code'] != $payment_code) {
				$edit_data['pay_alter'] = 1;
			} else $edit_data['pay_alter'] = 0;
		
			// 如果付款方式是货到付款，则变更交易类型
			if(in_array($orderInfo['bizIdentity'], array(Def::TRADE_ORDER)) && in_array(strtoupper($payment_code), array('COD'))) {
				$edit_data['payType'] = 'COD';
			}
			
			// 资金渠道
			$edit_data['fundchannel'] = Language::get(strtolower($payment_code));
			
			$model = parent::find()->select('trade_id')->where(['trade_id' => $tradeInfo['trade_id']])->one();
			foreach($edit_data as $k => $v) {
				$model->$k = $v;
			}
			if($model->save() === false) {
				return false;
			}
			
			// 更新引用数值
			$orderInfo['tradeList'][$key] = array_merge($orderInfo['tradeList'][$key], $edit_data);
		}
	
		return true;
	}	

	/* 更新payTradeNo */
	public static function updatePayTradeNo($tradeNo, $payTradeNo = '')
	{
		
		$edit_data = array('payTradeNo' => $payTradeNo);
		
		$model = parent::find()->select('trade_id')->where(['tradeNo' => $tradeNo])->one();
		foreach($edit_data as $k => $v) {
			$model->$k = $v;
		}
		if($model->save() === false) {
			return false;
		}
		
		return true;
	}
}
