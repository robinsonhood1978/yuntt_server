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
 * @Id GoodsPropValueModel.php 2018.5.3 $
 * @author mosir
 */

class GoodsPropValueModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_prop_value}}';
    }
	
	// 关联表
	public function getGoodsProp()
	{
		return parent::hasOne(GoodsPropModel::className(), ['pid' => 'pid']);
	}
}
