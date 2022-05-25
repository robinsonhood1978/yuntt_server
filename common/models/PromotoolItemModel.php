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
 * @Id PromotoolItemModel.php 2018.5.7 $
 * @author mosir
 */

class PromotoolItemModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%promotool_item}}';
    }
	
	/* 获取某个卖家设置的营销工具详细信息，并格式化配置项 */
	public static function getInfo($appid, $store_id = 0, $params = array(), $format = true)
	{
		$result = false;
		
		if($appid && $store_id)
		{
			$query = parent::find()->where(['appid' => $appid, 'store_id' => $store_id]);
			if($params) {
				$query->andWhere($params);
			}
			$result = $query->one();
			if(($result = $query->one()) && $result->config && $format) {
				$result->config = unserialize($result->config);
			}
		}
		return $result ? ArrayHelper::toArray($result) : false;
	}
}
