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
 * @Id AddressModel.php 2018.4.22 $
 * @author mosir
 */

class AddressModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%address}}';
    }
	
	/* 确保有一个默认收货地址的存在 */
	public static function setIndexAddress($addr_id = 0)
	{
		if($addr_id && ($model = self::find()->where(['userid' => Yii::$app->user->id, 'addr_id' => $addr_id])->one())) {
			$model->defaddr = 1;
			if($model->save()) {
				self::updateAll(['defaddr' => 0], ['and', ['userid' => Yii::$app->user->id], ['!=', 'addr_id', $addr_id]]);
				return true;
			}
		}
		elseif(!self::find()->where(['userid' => Yii::$app->user->id, 'defaddr' => 1])->exists()) {
			if($model = self::find()->where(['userid' => Yii::$app->user->id])->orderBy(['addr_id' => SORT_DESC])->one()) {
				$model->defaddr = 1;
				return $model->save() ? true : false;
			}
		}
		return false;
	}
}
