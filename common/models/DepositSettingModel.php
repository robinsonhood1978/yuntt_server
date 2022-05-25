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
 * @Id DepositSettingModel.php 2018.4.2 $
 * @author mosir
 */


class DepositSettingModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%deposit_setting}}';
    }
	
	/**
	 * 取系统配置 
	 */
	public static function getSystemSetting()
	{
		if(!($setting = parent::find()->where(['userid' => 0])->asArray()->one())) {
			$model = new DepositSettingModel();
			$model->userid = 0;
			$model->save(false);
			
			return ArrayHelper::toArray($model);
		}

		return $setting;
	}
	
	/**
	 * 取用户配置
	 * 如果用户配置的某项小于0，则取系统配置
	 */
	public static function getDepositSetting($userid = 0, $field = null)
	{
		$setting = self::getSystemSetting();
		if(($query = parent::find()->select('trade_rate,transfer_rate,regive_rate,guider_rate')->where(['userid' => $userid])->asArray()->one())) {
			foreach($query as $key => $value) {
				if($value < 0) {
					unset($query[$key]);
				}
			}

			// 由于部分配置取系统，部分取用户，所以下面两个参数值就无意义了
			unset($setting['setting_id'], $setting['userid']);
			$setting = array_merge($setting, $query);
		}

		if($field && isset($setting[$field])) {
			return $setting[$field] < 0 ? 0 : floatval($setting[$field]);
		}
		
		return $setting;
	}
}
