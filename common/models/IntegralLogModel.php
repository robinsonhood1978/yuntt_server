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

use common\library\Timezone;

/**
 * @Id IntegralLogModel.php 2018.4.17 $
 * @author mosir
 */


class IntegralLogModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%integral_log}}';
    }

	public static function addLog($data = array())
	{
		extract($data);
		
		$model = new IntegralLogModel();
		$model->userid = $userid;
		$model->order_id = $order_id ? $order_id : 0;
		$model->order_sn = $order_sn ? $order_sn : '';
		$model->changes = ($flow == 'minus') ? -$amount : $amount;
		$model->balance = $balance;
		$model->type = $type;
		$model->state = $state ? $state : 'finished';
		$model->flag = $flag ? $flag : '';
		$model->add_time = Timezone::gmtime();
		
		return $model->save() ? true : false;
	}
}
