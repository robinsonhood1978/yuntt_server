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

use common\library\Language;
use common\library\Timezone;

/**
 * @Id RefundModel.php 2018.4.1 $
 * @author mosir
 */

class RefundModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%refund}}';
    }
	
	// 关联表
	public function getDepositTrade()
	{
		return parent::hasOne(DepositTradeModel::className(), ['tradeNo' => 'tradeNo']);
	}
	
	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'seller_id']);
	}
	
	// 关联表
	public function getRefundBuyerInfo()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'buyer_id']);
	}
	// 关联表
	public function getRefundSellerInfo()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'seller_id']);
	}
	// 关联表
	public function getRefundMessage()
	{
		return parent::hasMany(RefundMessageModel::className(), ['refund_id' => 'refund_id']);
	}
	
	public static function genRefundSn()
	{
		// 选择一个随机的方案
        mt_srand((double) microtime() * 1000000);
	
        $refund_sn = Timezone::localDate('YmdHis', Timezone::gmtime()) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT).mt_rand(10, 99);
        if (!parent::find()->where(['refund_sn' => $refund_sn])->exists()) {
            return $refund_sn;
        }
        // 如果有重复的，则重新生成
        return self::genRefundSn();
	}
	
	/* 查询该笔交易是否有退款 */
	public static function checkTradeHasRefund($tradeInfo = [])
	{
		$status_label = Language::get('TRADE_'.strtoupper($tradeInfo['status']));
		
		$refund = parent::find()->select('refund_id,status,total_fee,refund_total_fee')->where(['tradeNo' => $tradeInfo['tradeNo']])->asArray()->one();
		if($refund)
		{
			if(!in_array($refund['status'], array('CLOSED', 'SUCCESS'))) {
				$refund['status_label'] = Language::get('REFUND_'.strtoupper($refund['status']));
				$status_label = ($tradeInfo['buyer_id'] == Yii::$app->user->id) ? Language::get('has_apply_refund') : Language::get('party_apply_refund');
			}
			
			// 如果是退款成功，则获取退款的金额
			if(in_array($refund['status'], array('SUCCESS'))) {
				$refund['amount'] = $refund['refund_total_fee'];
			}
		}
		return array($refund, $status_label);
	}
}
