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

use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\DepositRechargeModel;
use common\models\CashcardModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id CardrechargeDepopay.php 2018.4.17 $
 * @author mosir
 */

class CardrechargeDepopay extends IncomeDepopay
{
	/**
	 * 针对交易记录的交易分类，值有：购物：SHOPPING； 理财：FINANCE；缴费：CHARGE； 还款：CCR；转账：TRANSFER ...
	 */
	public $_tradeCat  = 'RECHARGE'; 
	
	/**
	 * 针对财务明细的资金用途，值有：在线支付：PAY；充值：RECHARGE；提现：WITHDRAW; 服务费：SERVICE；转账：TRANSFER
	 */
    public $_tradeType = 'RECHARGE';
	
	public function submit($data = array())
	{
        extract($data);
		
        // 处理交易基本信息
        $base_info = parent::_handle_trade_info($trade_info);
		if (!$base_info) {
            return false;
        }
		
		//$tradeNo = $extra_info['tradeNo'];
		
		/* 插入充值记录 */
		if(!$tradeInfo = $this->_insert_recharge_info($trade_info, $extra_info)) {
			$this->setErrors('50005');
			return false;
		}
		/* 插入收支记录 */
		if(!$this->_insert_record_info($tradeInfo)) {
			$this->setErrors('50020');
			return false;
		}
					
		return true;
	}
	
	/* 插入交易记录，充值记录 */
	private function _insert_recharge_info($trade_info, $extra_info)
	{
		// 如果添加有记录，则不用再添加了
		if(!($tradeInfo = DepositTradeModel::find()->where(['tradeNo' => $extra_info['tradeNo']])->one()))
		{
			// 增加交易记录
			$data_trade = array(
				'tradeNo'		=> $extra_info['tradeNo'],
				'payTradeNo'	=> DepositTradeModel::genPayTradeNo(),
				'bizOrderId'	=> $extra_info['bizOrderId'],
				'bizIdentity'	=> Def::TRADE_RECHARGE,
				'buyer_id'		=> $trade_info['userid'],
				'seller_id'		=> $trade_info['party_id'],
				'amount'		=> $trade_info['amount'],
				'status'		=> 'PENDING',
				'payment_code'	=> 'RECHARGECARD',
				'tradeCat'		=> $this->_tradeCat,
				'payType'		=> $this->_payType,
				'flow'     		=> $this->_flow,
				'fundchannel'   => Language::get('rechargecard'),
				'title'			=> Language::get('recharge') . ' - ' . Language::get('rechargecard'),
				'buyer_remark'	=> $extra_info['bizOrderId'],
				'add_time'		=> Timezone::gmtime()
			);
			
			$model = new DepositTradeModel();
			foreach($data_trade as $key => $val) {
				$model->$key = $val;
			}
		
			if($model->save())
			{
				$query = new DepositRechargeModel();
				$query->orderId = $extra_info['bizOrderId'];
				$query->userid = $trade_info['userid'];
				$query->is_online = 0;
	
				if($query->save()) {
					$tradeInfo = $model;
				}
			}
		}
		
		return $tradeInfo;
	}
	
	/* 充值卡充值 */
	private function _insert_record_info($tradeInfo = null)
	{
		$time = Timezone::gmtime();
		
		// 修改交易状态
		DepositTradeModel::updateAll(['status' => 'SUCCESS', 'pay_time' => $time, 'end_time' => $time], ['tradeNo' => $tradeInfo->tradeNo]);
			
		// 插入充值者收入记录
		$model = new DepositRecordModel();
		$model->tradeNo = $tradeInfo->tradeNo;
		$model->userid = $tradeInfo->buyer_id;
		$model->amount = $tradeInfo->amount;
		$model->balance = parent::_update_deposit_money($tradeInfo->buyer_id, $tradeInfo->amount);
		$model->tradeType = $this->_tradeType;
		$model->tradeTypeName = Language::get(strtoupper($this->_tradeType));
		$model->flow = $this->_flow;
		
		if(!$model->save()) {
			return false;
		}
		if($this->post->card_id) {
			CashcardModel::updateAll(['useId' => $model->userid, 'active_time' => Timezone::gmtime()], ['id' => $this->post->card_id]);

			// 激活充值成功后，账户余额中该部分金钱设置为不可提现
			DepositAccountModel::updateAllCounters(['nodrawal' => $tradeInfo->amount], ['userid' => $model->userid]);
		}
		
		return true;
	}
}