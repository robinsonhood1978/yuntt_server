<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business;

use yii;

use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id BaseDepopay.php 2018.4.12 $
 * @author mosir
 */
 
class BaseDepopay
{
	/**
	 * 交易类型
	 * @var string $otype
	 */
	protected $otype = '';
	
	/**
	 * 页面提交参数
	 * @var object $post
	 */
	public $post = null;

	/**
	 * 其他额外参数
	 * @var array $params
	 */
	public $params = array();

	/**
	 * 错误捕捉
	 * @var object $errors
	 */
	public $errors = null;

	/**
	 * 错误代码集合
	 * @var array $errorCode
	 */
	public $errorCode = [];
	
	public function __construct($otype, $post = null, $params = array())
	{
		$this->otype 	= $otype;
		$this->post 	= $post;
		$this->params 	= $params;
	}
	
	/* 验证账户余额是否足够 */
	public function _check_enough_money($money, $userid)
	{
		return DepositAccountModel::checkEnoughMoney($money, $userid);
	}
	
	/* 获取当前账户余额，或者冻结金额 */
	public function _get_deposit_balance($userid, $fields = 'money')
	{
		return DepositAccountModel::getDepositBalance($userid, $fields);
	}
	
	/* 更新账户余额，增加（如卖出商品）或者减少，并返回最新的余额 */
	public function _update_deposit_money($userid, $amount, $change = 'add')
	{
		return DepositAccountModel::updateDepositMoney($userid, $amount, $change);
	}
	
	/* 更新冻结金额，增加（如提现）或减少，并返回最新的金额 */
	public function _update_deposit_frozen($userid, $amount, $change = 'add')
	{
		return DepositAccountModel::updateDepositFrozen($userid, $amount, $change);
	}
	
	/*  更新交易状态 */
	public function _update_trade_status($tradeNo, $params = [])
	{
		if(($model = DepositTradeModel::find()->where(['tradeNo' => $tradeNo])->one()) && $params) {
			foreach($params as $key => $val) {
				$model->$key = $val;
			}
			return $model->save();
		}
		return false;
	}
	
	/*  更新订单状态 */
	public function _update_order_status($order_id, $params = [])
	{
		if(($model = \common\models\OrderModel::find()->where(['order_id' => $order_id])->one()) && $params) {
			foreach($params as $key => $val) {
				$model->$key = $val;
			}
			return $model->save();
		}
		return false;
	}
	
	/**
	 * 获取交易标题信息
	 */
	public function _get_intro_by_order($order_id = 0)
	{
		$intro = '';
		if(($query = \common\models\OrderGoodsModel::find()->select('goods_name')->where(['order_id' => $order_id]))) {	
			if($query->count() > 1) {
				$intro = $query->one()->goods_name . Language::get('and_more');
			} else $intro = $query->one()->goods_name;
		}
		return addslashes($intro);
	}
	
	/* 插入账户收支记录，并变更账户余额 */
	public function _insert_deposit_record($params = array(), $changeBalance = true)
	{
		// $data is array
		$model = new DepositRecordModel();
		foreach($params as $key => $val) {
			$model->$key = $val;
		}
		if($model->save()) {
			if($changeBalance == true) {
				if(DepositAccountModel::updateAll(['money' => $params['balance']], ['userid' => $params['userid']]) === false) {
					$model->delete();
					return false;
				}
				return true; //$model->record_id;
			}
			return true;
		}
		return false;
	}
	
	/* 系统扣费（交易，提现，转账等） */
	public function _sys_chargeback($tradeNo, $trade_info, $rate, $type = 'trade_fee')
	{
		// 费率不合理，不进行扣点
		if(!$rate || $rate <=0 || $rate >1) return true;
		
		$fee  = round($trade_info['amount'] * $rate, 2);
		
		if($fee <= 0) {
			return true;
		}
		
		if(is_array($type) || empty($type)) {
			$remark	= Language::get('trade_fee').'['.$tradeNo.']';
		} else $remark = Language::get($type).'['.$tradeNo.']';
		
		$time = Timezone::gmtime();
		$data_trade = array(
			'tradeNo'		=>	DepositTradeModel::genTradeNo(),
			'bizOrderId'	=>  DepositTradeModel::genTradeNo(12, 'bizOrderId'),
			'bizIdentity'	=>  Def::TRADE_CHARGE,
			'buyer_id'		=>	$trade_info['userid'],
			'seller_id'		=>	0,
			'amount'		=>	$fee,
			'status'		=>	'SUCCESS',
			'payment_code' 	=>  'deposit',
			'tradeCat'		=>	'SERVICE',// 服务费
			'payType'		=>	'INSTANT', 
			'flow'			=>	'outlay',
			'fundchannel'   =>  Language::get('deposit'),
			'title'			=>	Language::get('chargeback'),
			'add_time'		=>	$time,
			'pay_time'		=>	$time,
			'end_time'		=>	$time,
		);
		
		$model = new DepositTradeModel();
		foreach($data_trade as $key => $val) {
			$model->$key = $val;
		}
		
		if($model->save())
		{
			$data_record = array(
				'tradeNo'		=>	$data_trade['tradeNo'],
				'userid'		=>	$trade_info['userid'],
				'amount'		=>  $fee,
				'balance'		=>	$this->_update_deposit_money($trade_info['userid'], $fee, 'reduce'),
				'tradeType'		=>  'SERVICE',
				'tradeTypeName' => 	Language::get('SERVICE'),
				'flow'			=>	'outlay',
				'name'			=>	Language::get('chargeback'),
				'remark'		=>  $remark,
			);
			return $this->_insert_deposit_record($data_record, false);
		}
	}
	
	/**
	 * 如果是使用余额支付，且买家账户有不可提现金额
	 * 则解除该部分金额的提现额度限制，避免不可提现金额一直存在
	 */
	public function relieveUserNodrawal($tradeNo, $userid = 0, $money = 0)
	{
		$query = DepositTradeModel::find()->select('payment_code')->where(['tradeNo' => $tradeNo])->one();
		if(!$query || ($query->payment_code != 'deposit')) {
			return true;
		}

		$model = DepositAccountModel::find()->select('account_id,nodrawal')->where(['userid' => $userid])->one();
		if($model && ($model->nodrawal > 0)) {
			$model->nodrawal = ($model->nodrawal - $money) > 0 ? $model->nodrawal - $money : 0;
			return $model->save();
		}
		return false;
	}

	public function getErrors()
	{
		$ex = new DepopayException();
		if(is_array($this->errorCode) && count($this->errorCode) > 1){
			$error = '';
			foreach($this->errorCode as $k => $code)
			{
				$error .= ($k+1) . '. '. $ex->errorMsg[$code].'<br>';
			}
			return $error;
		}
		return $ex->errorMsg[$this->errorCode[0]];
	}

	public function setErrors($errorCode = '')
	{
		if(!empty($errorCode)) {
			$this->errorCode[] = $errorCode;
			$this->errors = $this->getErrors();
		}
	}
}

class DepopayException
{
	var $errorMsg;
	
	function __construct() {
		
		$this->errorMsg = array(
			"10001" => "交易金额不能小于零！",
            "50001" => "交易异常！扣除的交易服务费小于零元，或者所扣除的交易服务费大于交易金额。",
            "50002" => "交易异常！交易金额小于零元。",
            "50003" => "订单异常！找不到商户订单号。",
			"50004" => "充值异常！找不到指定的银行卡信息。",
			"50005" => "交易异常！无法正确添加充值记录。",
			"50006" => "对不起！在退款给买家过程中，插入收支记录失败。",
			"50007" => "对不起！订单退款记录添加失败。",
			"50008" => "对不起！卖家收支记录添加过程中出现异常。",
			"50009" => "平台扣除卖家手续费出现异常！",
			"50010" => "对不起！无法通过商户订单号查询到该订单的交易号。",
			"50011" => "交易异常！转账过程中，无法正确添加转出记录。",
			"50012" => "交易异常！转账过程中，无法正确添加转入记录。",
			"50013" => "退款异常！无法正确修改订单状态。",
			"50014" => "对不起！订单日志插入失败。",
			"50015" => "平台扣除转账手续费出现异常！",
			"50016" => "对不起！提现过程中插入收支记录出现异常。",
			"50017" => "对不起！提现过程中冻结资金更新出现异常。",
			"50018" => "对不起！在插入提现银行卡信息过程中出现错误。",
			"50019" => "对不起！您的账户余额不足。",
			"50020" => "交易异常！插入收支记录过程中出现问题。",
			"50021" => "交易异常！无法正确修改订单状态。",
			"50022" => "操作异常！买家确认收货后无法正常修改交易状态。",
			"50023" => "交易异常！取消订单中退回给买家款项时出现插入错误。",
			"50024" => "交易异常！无法正确修改交易状态信息。",
			"60001" => "交易异常！购买应用中无法正常支付",
			"60002" => "更新所购买应用的过期时间出现异常！",
			"60003" => "无法正常变更购买应用记录中的状态",
        );
    }  
}