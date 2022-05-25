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

use common\models\PluginModel;

/**
 * @Id CodModel.php 2018.6.29 $
 * @author mosir
 */

class CodModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cod}}';
    }

    /**
     * 获取指定店铺的货到付款配置信息
     * @param int $store_id
     */
    public static function checkAndGetInfo($store_id = 0)
    {
        $payment = PluginModel::find()->where(['instance' => 'payment', 'code' => 'cod', 'enabled' => 1])->asArray()->one();
        if(!$payment) {
            return false;
        }
       
        if(!$store_id || !($model = self::find()->where(['store_id' => $store_id, 'status' => 1])->one())) {
            return false;
        }
        $payment['regions'] = $model->regions;
        
        return (object) $payment;
    }
}
