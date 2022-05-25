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

use common\models\DepositTradeModel;
use common\models\TeambuyModel;
use common\models\TeambuyLogModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id BuygoodsDepopay.php 2018.7.18 $
 * @author mosir
 */
 
class BuygoodsDepopay extends OutlayDepopay
{
	/**
	 * 针对交易记录的交易分类，值有：购物：SHOPPING； 理财：FINANCE；缴费：CHARGE； 还款：CCR；转账：TRANSFER ...
	 */
	public $_tradeCat	= 'SHOPPING'; 
	
	/**
	 * 针对财务明细的交易类型，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW; 服务费：SERVICE；转账：TRANSFER
	 */
    public $_tradeType 	= 'PAY';
	
	/**
	 * 支付类型，值有：即时到帐：INSTANT；担保交易：SHIELD；货到付款：COD
	 */
	public $_payType   	= 'SHIELD';
	
	public function submit($data = array())
	{
        extract($data);
		
		if($trade_info['amount'] < 0) {
			$this->setErrors('10001');
			return false;
		}
		
		$tradeNo = $extra_info['tradeNo'];
		
		if(!DepositTradeModel::find()->where(['tradeNo' => $tradeNo])->exists()) 
		{
			$model = new DepositTradeModel();
			$model->tradeNo = $tradeNo;
			$model->bizOrderId = $extra_info['bizOrderId'];
			$model->bizIdentity = $extra_info['bizIdentity'];
			$model->buyer_id = $trade_info['userid'];
			$model->seller_id = $trade_info['party_id'];
			$model->amount = $trade_info['amount'];
			$model->status = 'PENDING';
			$model->tradeCat = $this->_tradeCat;
			$model->payType = $this->_payType;
			$model->flow = $this->_flow;
			$model->title = $extra_info['title'];
			$model->buyer_remark = isset($this->post->remark) ? $this->post->remark : '';
			$model->add_time = Timezone::gmtime();
			
			return $model->save() ? true : false;
		}
		return true;
	}
	
	/* 响应通知 */
	public function respond_notify($data = array())
	{
        extract($data);
		
        // 处理交易基本信息
        $base_info = parent::_handle_trade_info($trade_info);
		
        if (!$base_info) {
            return false;
        }
		
		$tradeNo = $extra_info['tradeNo'];
		
		// 修改交易状态为已付款
		if(!parent::_update_trade_status($tradeNo, array('status'=> 'ACCEPTED', 'pay_time' => Timezone::gmtime()))){
			$this->setErrors('50024');
			return false;
		}
				
		// 插入收支记录，并变更账户余额
		if(!$this->_insert_record_info($tradeNo, $trade_info, $extra_info)) {
			$this->setErrors('50020');
			return false;
		}
		
		// 修改订单状态为已付款
		if(!parent::_update_order_status($extra_info['order_id'], array('status'=> Def::ORDER_ACCEPTED, 'pay_time' => Timezone::gmtime()))) {
			$this->setErrors('50021');
			return false;
		}

		// 如果是拼团订单
		if($extra_info['otype'] == 'teambuy') {
			if(!$this->updateTeamBuyInfo($trade_info['userid'], $extra_info['order_id'])) {
				$this->setErrors('50024');
				return false;
			}
		}

		// 如果是社区团购订单
		if($extra_info['otype'] == 'guidebuy') {
			if(!$this->updateGuidebuyInfo($trade_info['userid'], $extra_info['order_id'])) {
				$this->setErrors('50024');
				return false;
			}
		}
	
		return true;
	}
	
	/* 插入收支记录，并变更账户余额 */
	private function _insert_record_info($tradeNo, $trade_info, $extra_info)
	{
		$result = true;
		
		//  加此判断，目的为允许提交订单金额为零的处理
		if($trade_info['amount'] > 0)
		{
			$data_record = array(
				'tradeNo'		=>	$tradeNo,
				'userid'		=>	$trade_info['userid'],
				'amount'		=> 	$trade_info['amount'],
				'balance'		=>	parent::_update_deposit_money($trade_info['userid'],  $trade_info['amount'], 'reduce'),
				'tradeType'		=>  $this->_tradeType,
				'tradeTypeName' => 	Language::get(strtoupper($this->_tradeType)),
				'flow'			=>	$this->_flow,
			);
			$result = parent::_insert_deposit_record($data_record);
		}
		return $result;
	}

	/**
	 * 修改拼团订单信息
	 */
	private function updateTeamBuyInfo($userid, $order_id) 
	{
		$query = TeambuyLogModel::find()->select('logid,teamid,people')->where(['userid' => $userid, 'order_id' => $order_id])->one();
		if(!$query) {
			return false;
		}

		// 付款时间
		$query->pay_time = Timezone::gmtime();
		$query->save();

		// 找出已付款的拼单
		$teambuylogs = TeambuyLogModel::find()->select('logid,order_id')->where(['and', ['teamid' => $query->teamid, 'status' => 0], ['>', 'pay_time', 0]]);

		// 满足成团条件
		if($teambuylogs->count() >= $query->people) {
			foreach($teambuylogs->all() as $model) {
				$model->status = 1; // 设置为成团状态
				if($model->save()) {

					// 修改状态为已付款待发货
					parent::_update_order_status($model->order_id, array('status' => Def::ORDER_ACCEPTED));
				}
			}
		} else {
			
			//  如果不满足成团，把订单状态调整为待成团
			parent::_update_order_status($order_id, array('status' => Def::ORDER_TEAMING));
		}

		return true;
	}

	/**
	 * 修改社区团购订单信息
	 */
	private function updateGuidebuyInfo($userid, $order_id) 
	{
		// 修改订单状态为待配送（社区团购订单没有商家发货环节，统一由平台完成到店配送）
		return parent::_update_order_status($order_id, array('status' => Def::ORDER_PICKING));
	}
}
