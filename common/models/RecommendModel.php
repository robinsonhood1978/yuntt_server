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
 * @Id RecommendModel.php 2018.8.14 $
 * @author mosir
 */

class RecommendModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%recommend}}';
    }
	
	// 关联表
	public function getRecommendGoods()
	{
		return parent::hasMany(RecommendGoodsModel::className(), ['recom_id' => 'recom_id']);
	}
}
