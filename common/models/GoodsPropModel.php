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
 * @Id GoodsPropModel.php 2018.5.3 $
 * @author mosir
 */

class GoodsPropModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%goods_prop}}';
    }
	
	// 关联表
	public function getGoodsPropValue()
	{
		return parent::hasMany(GoodsPropValueModel::className(), ['pid' => 'pid']);
	}
}
