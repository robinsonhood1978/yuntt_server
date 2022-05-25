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

/**
 * @Id DepositRechargeMode.php 2018.4.17 $
 * @author mosir
 */


class DepositRechargeModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deposit_recharge}}';
    }
	
	// 关联表
	public function getDepositTrade()
	{
		return parent::hasOne(DepositTradeModel::className(), ['bizOrderId' => 'orderId']);
	}
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}
}
