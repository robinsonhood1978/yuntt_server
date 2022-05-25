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
use yii\helpers\ArrayHelper;

use common\models\DepositTradeModel;
use common\models\DepositRecordModel;
use common\models\RefundModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id DepositTradelistForm.php 2018.9.28 $
 * @author mosir
 */
class DepositTradelistForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4) 
	{
		$query = DepositTradeModel::find()->where(['buyer_id' => Yii::$app->user->id])->orWhere(['seller_id' => Yii::$app->user->id])->orderBy(['trade_id' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $record)
		{
			if(Basewind::getCurrentApp() == 'wap') {
				$recordlist[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);
			}
			
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
		return array($recordlist, $page);
	}
	
	/* 统计总交易额（支出也累加）和交易笔数 for WAP */
	public function getTotal()
	{
		$list = DepositRecordModel::find()->alias('dr')->select('flow,amount')->where(['userid' => Yii::$app->user->id])
			//->joinWith('depositTrade dt', false)
			->orderBy(['record_id' => SORT_DESC])->asArray()->all();
		
		$amount = $quantity = 0;
		foreach($list as $key => $val) {
			$amount += $val['amount'];
			$quantity++;
		}
		return array($amount, $quantity);
	}
	
	public function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['add_time_from', 'add_time_to'])) {
					return true;
				}
			}
			return false;
		}
		if($post->add_time_from) {
			$query->andWhere(['>=', 'add_time', Timezone::gmstr2time($post->add_time_from)]);
		}
		if($post->add_time_to) {
			$query->andWhere(['<=', 'add_time', Timezone::gmstr2time($post->add_time_to)]);
		}
		
		return $query;
	}
}
