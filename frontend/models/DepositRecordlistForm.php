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

use common\models\DepositRecordModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id DepositRecordlistForm.php 2018.9.28 $
 * @author mosir
 */
class DepositRecordlistForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4) 
	{
		$query = DepositRecordModel::find()->alias('dr')->select('dr.userid,dr.amount,dr.balance,dr.flow,dr.tradeTypeName,dt.tradeNo,dt.bizOrderId,dt.buyer_id,dt.seller_id,dt.status,dt.fundchannel,add_time,pay_time,end_time')->joinWith('depositTrade dt', false)->where(['userid' => Yii::$app->user->id])->orderBy(['record_id' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($recordlist as $key => $record)
		{
			if(Basewind::getCurrentApp() == 'wap') {
				$recordlist[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);
			}
		}
		return array($recordlist, $page);
	}
	
	/* 统计总收入和总支出 */
	public function getTotal()
	{
		$list = DepositRecordModel::find()->alias('dr')->select('flow,amount')->where(['userid' => Yii::$app->user->id])
			//->joinWith('depositTrade dt', false)
			->orderBy(['record_id' => SORT_DESC])->asArray()->all();
		
		$income = $outlay = 0;
		foreach($list as $key => $val) {
			if($val['flow'] == 'income') $income += $val['amount'];
			else $outlay += $val['amount'];
		}
		return array($income, $outlay);
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
