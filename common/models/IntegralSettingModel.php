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
 * @Id IntegralSettingModel.php 2018.8.6 $
 * @author mosir
 */

class IntegralSettingModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%integral_setting}}';
    }
	
	/* 获取系统的积分配置数据，参数为字符串或者数组 */
	public static function getSysSetting($params = null)
	{
		if(!($setting = self::find()->asArray()->one())) {
			return 0;
		}
		
		$setting['buygoods'] = unserialize($setting['buygoods']);
		if(is_array($params))
		{	
			// 只要前两个值
			list($val1,$val2) = $params;
			return $setting[$val1][$val2];
		}
		elseif($params !== null) {
			return isset($setting[$params]) ? $setting[$params] : 0;
		}
		else {
			return $setting;
		}
	}
}
