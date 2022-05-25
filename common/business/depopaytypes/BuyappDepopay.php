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
use common\models\AppmarketModel;
use common\models\ApprenewalModel;
use common\models\AppbuylogModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id BuyappDepopay.php 2018.7.18 $
 * @author mosir
 */
 
class BuyappDepopay extends OutlayDepopay
{
    /**
	 * 针对交易记录的交易分类，值有：购物：SHOPPING； 理财：FINANCE；缴费：CHARGE； 还款：CCR；转账：TRANSFER ...
	 */
	public $_tradeCat	= 'SHOPPING'; 
	
	/**
	 * 针对财务明细的交易类型，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW; 服务费：SERVICE；转账：TRANSFER
	 */
    public $_tradeType 	= 'PAY';
	
	public function submit($data = array())
	{
        extract($data);
		
		if($trade_info['amount'] <= 0) {
			$this->setErrors("10001");
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
			$model->buyer_remark = $this->post->remark ? $this->post->remark : '';
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
		
        if (!$base_info)
        {
            // 基本信息验证不通过
            return false;
        }
		
		$tradeNo = $extra_info['tradeNo'];
		
		// 修改交易状态为已付款
		if(!parent::_update_trade_status($tradeNo, array('status'=> 'SUCCESS', 'pay_time' => Timezone::gmtime(), 'end_time' => Timezone::gmtime()))){
			$this->setErrors('50024');
			return false;
		}
		
		// 插入收支记录，并变更账户余额
		if(!$this->_insert_record_info($tradeNo, $trade_info, $extra_info)) {
			$this->setErrors('50020');
			return false;
		}
		
		// 修改购买应用状态为交易完成
		if(!$this->_update_order_status($extra_info['bid'], array('status' => Def::ORDER_FINISHED, 'pay_time' => $time, 'end_time' => Timezone::gmtime()))) {
			$this->setErrors('60003');
			return false;
		}
		
		// 更新所购买的应用的过期时间
		if(!$this->_update_order_period($trade_info, $extra_info)) {
			$this->setErrors('60002');
			return false;
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
	
	public function _update_order_period($trade_info, $extra_info)
	{
		$result = false;
		
		$period = $extra_info['period'];
		if(($model = ApprenewalModel::checkIsRenewal($extra_info['appid'], $trade_info['userid']))) {
			$model->expired = strtotime("+{$period} months", $model->expired);
		}
		else
		{
			$model = new ApprenewalModel();
			$model->appid = $extra_info['appid'];
			$model->userid = $trade_info['userid'];
			$model->add_time = Timezone::gmtime();
			$model->expired = strtotime("+{$period} months", Timezone::gmtime());
		}
		
		// 更新销量
		if($model->save() && ($query = AppmarketModel::find()->where(['appid' => $extra_info['appid']])->one())) {
			$result = $query->updateCounters(['sales' => 1]);
		}
		
		return $result;
	}
	
	public function _update_order_status($bid = 0, $data = array())
	{
		if(($model = AppbuylogModel::find()->where(['bid' => $bid])->one())) {
			foreach($data as $key => $val) {
				$model->$key = $val;
			}
			return $model->save() ? true : false;
		}
		return false;
	}
	
}
