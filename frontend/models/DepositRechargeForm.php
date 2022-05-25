<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model;

use common\models\DepositTradeModel;

use common\library\Language;
use common\library\Business;
use common\library\Plugin;

/**
 * @Id DepositRechargeForm.php 2018.7.23 $
 * @author mosir
 */
class DepositRechargeForm extends Model
{
	public $errors = null;
	
	/* 获取充值交易（如没有则创建） */
	public function formData($post = null)
	{
		// 创建新的充值交易
		if(empty($post->tradeNo)) 
		{
			$tradeNo = DepositTradeModel::genTradeNo();
			$payment_code = $post->payment_code;
			
			// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
			$depopay_type = Business::getInstance('depopay')->build('recharge', $post);
					
			// 插入充值记录表，状态为：待付款
			$result = $depopay_type->submit(array(
				'trade_info' =>  array('userid' => Yii::$app->user->id, 'party_id' => 0, 'amount' => $post->money),
				'extra_info' =>  array('tradeNo' => $tradeNo, 'is_online' => 1)
			));
				
			if(!$result) {
				$this->errors = $depopay_type->errors;
				return false;
			}
			
		}
		// 这是再次发起的充值操作（原先已经创建了充值交易）
		else
		{
			$tradeNo = $post->tradeNo;
			$tradeInfo = DepositTradeModel::find()->select('payment_code,amount')->where(['buyer_id' => Yii::$app->user->id, 'tradeNo' => $tradeNo])->one();
			if(empty($tradeInfo)) {
				$this->errors = Language::get('no_data');
				return false;
			}
			$payment_code = $tradeInfo->payment_code;
		}
		
		$payment = Plugin::getInstance('payment')->build();
		$all_payments = $payment->getEnabled(0, true, ['not in', 'code', ['deposit', 'cod']]);
		if(!in_array($payment_code, $payment->getKeysOfPayments($all_payments))) {
			$this->errors = Language::get('payment_not_available');
			return false;
		}
		return array($tradeNo, $payment_code);
	}
}