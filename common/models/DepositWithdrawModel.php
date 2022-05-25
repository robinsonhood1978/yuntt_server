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
 * @Id DepositWithdrawModel.php 2018.4.3 $
 * @author mosir
 */

class DepositWithdrawModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deposit_withdraw}}';
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
