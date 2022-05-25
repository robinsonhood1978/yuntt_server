<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\business\depopaytypes;

use yii;

use common\models\DistributeModel;
use common\models\DistributeMerchantModel;
use common\models\DistributeOrderModel;
use common\models\OrderGoodsModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id DistributeDepopay.php 2018.10.15 $
 * @author mosir
 */

class DistributeDepopay extends IncomeDepopay
{
    /**
	 * 针对交易记录的交易分类，值有：购物：SHOPPING； 理财：FINANCE；缴费：CHARGE； 还款：CCR；转账：TRANSFER ...
	 */
	public $_tradeCat  = 'TRANSFER'; 
	
	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW; 服务费：SERVICE；转账：TRANSFER
	 */
    public $_tradeType = 'TRANSFER';
	
	public function submit($data = array())
	{
		// NOT TO...
	}

	/**
	 * 订单完成后分销返现。该操作可以不受是否开启三级分销的影响 
	 * 运费金额不参与返佣
	 */
	public function distribute($order = array())
	{
        $items = OrderGoodsModel::find()->select('rec_id,price,quantity,inviteType,inviteRatio,inviteUid')->where(['order_id' => $order['order_id']])->indexBy('rec_id')->all();
		if(empty($items)) {
			return false;
		}
		
		// 可返佣的金额基数小于零
		$distributeAmount = DistributeModel::getDistributeAmount($order);
		if($distributeAmount <= 0) {
			return false;
		}
		
		list($each) = DistributeModel::getItemRate($items, $distributeAmount);
		
		// 无需分佣
		if($each === false) {
			return false;
		}
		
		// 三级返佣总金额
		$amount = 0;
		$profit = array();
						
		// 计算该笔订单应该返现多少金额
		foreach($items as $key => $item)
		{
			$money = $ratio = array();
			if($item->inviteRatio && ($inviteRatio = unserialize($item->inviteRatio))) 
			{
				for($i = 1; $i <= 3; $i++) {
					$ratio[$i] = $inviteRatio['ratio'.$i];
					$money[$i] = round($distributeAmount * $each[$key] * $ratio[$i], 2);
				}
			}

			$parents = DistributeMerchantModel::getParents($item->inviteUid);
			foreach($parents as $i => $parent)
			{
				if(!$parent) {
					break;
				}
				
				$profit[$key][] = array(
					'userid'=> $parent, 
					'money'	=> $money[$i+1], 
					'layer' => $i+1, 
					'type' 	=> $item->inviteType, 
					'ratio' => $ratio[$i+1]
				);
				$amount += $money[$i+1];
			}
		}
		
		// 先从供货商处扣除返佣总额
		$options = ['title' => sprintf(Language::get('distribute_outlay'), $order['order_sn'])];
		if(!$this->distributeProfit($order['seller_id'], $amount, $order['order_sn'], 'reduce', 0, 'goods', $options)) {
			return false;
		}
		
		// 给每个上级返佣
		foreach($profit as $key => $val) {
			foreach($val as $k => $v) {
				$options = [
					'title' 	=> sprintf(Language::get('distribute_income'), $order['order_sn']), 
					'remark' 	=> Language::get('distribute_layer'.$v['layer']),
					'ratio'		=> $v['ratio'],
					'rec_id'    => $key,
				];
				
				$this->distributeProfit($v['userid'], $v['money'], $order['order_sn'], 'add', $v['layer'], $v['type'], $options);
			}
		}
		
		return true;
	}
	
	/**
	 * 	@var string $change  add|reduce
	 */
	private function distributeProfit($userid, $money, $order_sn, $change = 'add', $layer = 0, $type = 'goods', $options = array())
	{
		if(!$userid || !$money || ($money < 0)) {
			return false;
		}
		
		$model = new DepositTradeModel();
		$model->tradeNo = $model->genTradeNo();
		$model->bizOrderId = $model->genTradeNo(12, 'bizOrderId');
		$model->bizIdentity = Def::TRADE_FX;
		$model->buyer_id = $userid;
		$model->seller_id = 0;
		$model->amount = $money;
		$model->status = 'SUCCESS';
		$model->payment_code = 'deposit';
		$model->fundchannel = Language::get('deposit');
		$model->tradeCat = $this->_tradeCat;
		$model->payType = $this->_payType;
		$model->flow = $change == 'add' ? 'income' : 'outlay';
		$model->title = $options['title'];
		$model->add_time = Timezone::gmtime();
		$model->pay_time = Timezone::gmtime();
		$model->end_time = Timezone::gmtime();
		
		if($model->save())
		{
			$query = new DepositRecordModel();
			$query->tradeNo = $model->tradeNo;
			$query->userid = $model->buyer_id;
			$query->amount = $model->amount;
			$query->balance = parent::_update_deposit_money($model->buyer_id, $model->amount, $change);
			$query->tradeType = $this->_tradeType;
			$query->tradeTypeName = Language::get('transfer');
			$query->flow = $model->flow;
			$query->name = $model->title;
			$query->remark = isset($options['remark']) ? $options['remark'] : '';			
			if($query->save())
			{
				// 只统计增加的收益，扣减供货商的返现金额不统计
				if($change == 'add') {
					
					if(!($post = DistributeModel::find()->where(['userid' => $userid])->one())) {
						$post = new DistributeModel();
						$post->userid = $userid;
						$post->save();
					}
					// 汇总总收益
					$post->updateCounters(['amount' => $money]);
					
					// 汇总分别通过商品分销/店铺分销的总收益
					if(in_array($type, ['goods', 'store'])) {
						$post->updateCounters([$type => $money]);
					}
					
					// 汇总每一级分销的总收益
					if(in_array($layer, [1,2,3]) && ($field = 'layer'.$layer)) {
						$post->updateCounters([$field => $money]);
					}
					
					// 插入分销订单表记录
					$this->insertDistributeOrder($userid, $money, $order_sn, $model->tradeNo, $layer, $type, $options);
				}
				return true;
			}
		}
		return false;
	}
	
	private function insertDistributeOrder($userid, $money, $order_sn, $tradeNo, $layer, $type, $options = array())
	{
		$model = new DistributeOrderModel();
		$model->rec_id = $options['rec_id'];
		$model->userid = $userid;
		$model->money = $money;
		$model->tradeNo = $tradeNo;
		$model->order_sn = $order_sn;
		$model->layer = $layer;
		$model->ratio = $options['ratio'];
		$model->type = $type;
		$model->created = Timezone::gmtime();
		if(!$model->save()) {
			return false;
		}
		return true;
	}
}