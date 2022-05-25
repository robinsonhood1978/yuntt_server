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
 * @Id OrderGoodsModel.php 2018.3.29 $
 * @author mosir
 */


class OrderGoodsModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%order_goods}}';
    }
	
	// 关联表
	public function getOrder()
	{
		return parent::hasOne(OrderModel::className(), ['order_id' => 'order_id']);
	}
}
