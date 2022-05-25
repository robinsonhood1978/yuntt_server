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

use common\models\DepositSettingModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id SellgoodsDepopay.php 2018.4.24 $
 * @author mosir
 */
 
class SellgoodsDepopay extends IncomeDepopay
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
		
        // 处理交易基本信息
        $base_info = $this->_handle_trade_info($trade_info);
		$order_info = $this->_handle_order_info($extra_info);
        if (!$base_info || !$order_info) {
            return false;
        }
		
		$tradeNo = $extra_info['tradeNo'];
		
		// 修改交易状态为交易完成
		if(!$this->_update_trade_status($tradeNo, array('status' => 'SUCCESS', 'end_time' => Timezone::gmtime()))){
			$this->setErrors("50022");
			return false;
		}
		
		// 如果是货到付款的订单，则执行到此就可以结束了
		if(in_array($extra_info['payment_code'], array('cod'))) {
			return true;
			exit;
		}
		
		// 插入收支记录，并变更账户余额，变更买家收支记录状态，插入卖家收支记录
		if(!$this->_insert_record_info($tradeNo, $trade_info, $extra_info)) {
			$this->setErrors("50008");
			return false;
		}
		
		// 如果有交易服务费，则扣除卖家手续费
		if($trade_rate = DepositSettingModel::getDepositSetting($trade_info['userid'], 'trade_rate')) {
			if(!parent::_sys_chargeback($tradeNo, $trade_info, $trade_rate, 'trade_fee')) {
				$this->setErrors("50009");
				return false;
			}
		}

		// 如果是购物订单且买家使用的是余额支付，则处理不可提现金额的额度
		if($trade_info['amount'] > 0) {
			parent::relieveUserNodrawal($tradeNo, $trade_info['party_id'], $trade_info['amount']);
		}
		
		return true;
	}
	
	/* 插入收支记录，并变更账户余额 */
	public function _insert_record_info($tradeNo, $trade_info, $extra_info)
	{	
		// 增加卖家的收支记录
		$data_record['tradeNo']			= 	$tradeNo;
		$data_record['userid']			=	$trade_info['userid']; // 卖家ID
		$data_record['amount']  		=   $trade_info['amount'];
		$data_record['balance']			=	parent::_update_deposit_money($trade_info['userid'],  $trade_info['amount']); // 增加后的余额
		
		$data_record['tradeType']		=	$this->_tradeType;
		$data_record['tradeTypeName'] 	= 	Language::get(strtoupper($this->_tradeType));
		$data_record['flow']			=	$this->_flow;
			
		// 插入卖家的收支记录
		return parent::_insert_deposit_record($data_record, false);
	}
}
