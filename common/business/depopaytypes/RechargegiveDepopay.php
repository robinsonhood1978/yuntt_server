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
use common\models\DepositSettingModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id RechargegiveDepopay.php 2018.7.22 $
 * @author mosir
 */
 
class RechargegiveDepopay extends IncomeDepopay
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
	
	/* 插入交易记录，充值记录 */
	public function rechargegive($orderInfo = array())
	{
		// 如果充值返金额比例为零，则不处理
		$rate = floatval(DepositSettingModel::getDepositSetting($orderInfo['buyer_id'], 'regive_rate'));
		if(!$rate || (round($orderInfo['amount'] * $rate, 2) <= 0)) {
			return false;
		}
		
		// 实际上，只存在一次循环
		foreach($orderInfo['tradeList'] as $tradeInfo)
		{
			// 如果已返过，则不处理
			if(DepositTradeModel::find()->where(['bizOrderId' => $tradeInfo['tradeNo'], 'bizIdentity' => Def::TRADE_REGIVE])->exists()) {
				return false;
			}
				
			// 增加交易记录
			$data_trade = array(
				'tradeNo'		=> DepositTradeModel::genTradeNo(),
				'payTradeNo'	=> DepositTradeModel::genPayTradeNo(),
				'bizOrderId'	=> $tradeInfo['tradeNo'],
				'bizIdentity'	=> Def::TRADE_REGIVE,
				'buyer_id'		=> $tradeInfo['buyer_id'],
				'seller_id'		=> 0,
				'amount'		=> round($tradeInfo['amount'] * $rate, 2),
				'status'		=> 'SUCCESS',
				'payment_code'	=> 'deposit',
				'tradeCat'		=> $this->_tradeCat,
				'payType'		=> $this->_payType,
				'flow'     		=> $this->_flow,
				'fundchannel'   => Language::get('deposit'),
				'title'			=> Language::get('recharge_give'),
				'buyer_remark'	=> '',
				'add_time'		=> Timezone::gmtime(),
				'pay_time'		=> Timezone::gmtime(),
				'end_time'		=> Timezone::gmtime()
			);
				
			$model = new DepositTradeModel();
			foreach($data_trade as $key => $val) {
				$model->$key = $val;
			}
			
			if($model->save(false) == true)
			{
				$data_record = array(
					'tradeNo'		=>	$data_trade['tradeNo'],
					'userid'		=> 	$data_trade['buyer_id'],
					'amount'		=>  $data_trade['amount'],
					'balance'		=>	parent::_update_deposit_money($data_trade['buyer_id'], $data_trade['amount']),// 增加后的余额
					'tradeType'		=>  $this->_tradeType,
					'tradeTypeName' => 	Language::get(strtoupper($this->_tradeType)),
					'flow'			=>	$this->_flow,
				);

				$result = parent::_insert_deposit_record($data_record, false);
			}
		}
		return $result;
	}
}
