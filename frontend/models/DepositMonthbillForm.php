<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\DepositRecordModel;

use common\library\Timezone;

/**
 * @Id DepositMonthbillForm.php 2018.4.23 $
 * @author mosir
 */
class DepositMonthbillForm extends Model
{
	public function formData($post = null)
	{
		$recordlist = DepositRecordModel::find()->alias('dr')->select('dr.tradeNo,dr.amount,dr.flow,dr.tradeType,dt.end_time')->joinWith('depositTrade dt', false)->where(['userid' => Yii::$app->user->id, 'status' => 'SUCCESS'])->andWhere(['>', 'end_time', 0])->orderBy(['record_id' => SORT_DESC])->asArray()->all();
		
		$monthbill = array();
		
		// 按月进行归类
		foreach($recordlist as $key => $val)
		{
			$year_month = Timezone::localDate('Y-m', $val['end_time']);
			$monthbill[$year_month][$val['flow'].'_money'] += $val['amount'];
			$monthbill[$year_month][$val['flow'].'_count'] += 1;
				
			// 如果是支出，判断是否是服务费
			if($val['flow'] == 'outlay' && ($val['tradeType'] == 'SERVICE'))
			{
				$monthbill[$year_month][$val['tradeType'].'_money'] += $val['amount'];
				$monthbill[$year_month][$val['tradeType'].'_count'] += 1;
			}
		}
		
		return $monthbill;		
	}
}
