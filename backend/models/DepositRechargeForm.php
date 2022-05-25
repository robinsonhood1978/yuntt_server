<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRechargeModel;
use common\models\DepositRecordModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id DepositRechargeForm.php 2018.8.20 $
 * @author mosir
 */
class DepositRechargeForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
    public function valid($post)
	{
		if($post->money <= 0) {
			$this->errors = Language::get('money_error');
			return false;
		}
		if(!in_array($post->money_change, ['add', 'reduce'])) {
			$this->errors = Language::get('recharge_error');
			return false;
		}
		if($post->money_change == 'reduce' && !DepositAccountModel::checkEnoughMoney($post->money, $this->userid)) {
			$this->errors = Language::get('money_error');
			return false;
		}
		
		return true;
	}
	
	/* 管理操作资金[充值/扣费]，如果执行不成功，删除已插入的记录 */
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$account = DepositAccountModel::find()->where(['userid' => $this->userid])->one();
		
		// 先创建交易记录
		$model = new DepositTradeModel();
		$model->tradeNo = DepositTradeModel::genTradeNo();
		$model->bizOrderId = DepositTradeModel::genTradeNo(12, 'bizOrderId');
		$model->bizIdentity = Def::TRADE_RECHARGE;
		$model->buyer_id = $account->userid;
		$model->seller_id = 0;
		$model->amount = floatval($post->money);
		$model->status = 'SUCCESS';
		$model->payment_code = 'deposit';
		$model->tradeCat = ($post->money_change == 'add') ? 'RECHARGE' : 'CHARGE';
		$model->payType	= 'INSTANT';
		$model->flow = ($post->money_change == 'add') ? 'income' : 'outlay';
		$model->fundchannel = Language::get('deposit');
		$model->title = ($post->money_change == 'add') ? Language::get('recharge') : Language::get('chargeback');
		$model->buyer_remark = $post->remark;
		$model->add_time = Timezone::gmtime();
		$model->pay_time = Timezone::gmtime();
		$model->end_time = Timezone::gmtime();
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		// 如果是充值
		if($post->money_change == 'add') 
		{
			$query = new DepositRechargeModel();
			$query->orderId = $model->bizOrderId;
			$query->userid = $model->buyer_id;
			$query->examine = Yii::$app->user->identity->username;
			$query->is_online = 1;
			if(!$query->save()) {
				$model->delete();
				$this->errors = $model->errors;
				return false;
			}
		}
		
		// 插入收支记录，并变更账户余额
		$record = new DepositRecordModel();
		$record->tradeNo = $model->tradeNo;
		$record->userid = $model->buyer_id;
		$record->amount = $model->amount;
		$record->balance = DepositAccountModel::updateDepositMoney($model->buyer_id, $model->amount, $post->money_change);
		$record->tradeType = $model->tradeCat;
		$record->tradeTypeName = $model->title;
		$record->flow = $model->flow;
		$record->remark = isset($post->remark) ? $post->remark : (($post->money_change == 'add') ? Language::get('system_recharge') : Language::get('system_chargeback'));
		if(!$record->save()) {
			DepositAccountModel::updateDepositMoney($model->buyer_id, $model->amount, 'reduce');
			$model->delete();
			$query->delete();
			$this->errors = $record->errors;
			return false;
		}
		return true;
	}
}
