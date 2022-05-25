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
use yii\helpers\ArrayHelper;

/**
 * @Id DistributeOrderModel.php 2018.10.22 $
 * @author mosir
 */

class DistributeOrderModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%distribute_order}}';
    }
	
	// 关联表
	public function getOrder()
	{
		return parent::hasOne(OrderModel::className(), ['order_sn' => 'order_sn']);
	}
	
	// 关联表
	public function getOrderGoods()
	{
		return parent::hasOne(OrderGoodsModel::className(), ['rec_id' => 'rec_id']);
	}
}
