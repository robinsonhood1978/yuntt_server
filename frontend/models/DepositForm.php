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

use common\models\DepositTradeModel;
use common\models\RefundModel;

/**
 * @Id DepositForm.php 2018.4.23 $
 * @author mosir
 */
class DepositForm extends Model
{
	public $errors = null;
	
	public function formData($limit = 10)
	{
		$recordlist = DepositTradeModel::find()->where(['buyer_id' => Yii::$app->user->id])
						->orWhere(['seller_id' => Yii::$app->user->id])->limit($limit)->orderBy(['trade_id' => SORT_DESC])->asArray()->all();
		
		foreach($recordlist as $key => $record)
		{
			// 如果当前用户是交易的卖方
			if($record['seller_id'] == Yii::$app->user->id) {
				$recordlist[$key]['flow'] = ($record['flow'] == 'income') ?  'outlay' : 'income';
			}
			
			// 交易的对方
			$recordlist[$key]['partyInfo'] = DepositTradeModel::getPartyInfoByRecord(Yii::$app->user->id, $record);
			
			// 赋值中文状态值（且检查是否退款）
			list($refund, $status_label) 		= RefundModel::checkTradeHasRefund($record);
			$recordlist[$key]['refund'] 		= $refund;
			$recordlist[$key]['status_label'] 	= $status_label;
		}
		return $recordlist;		
	}
}
