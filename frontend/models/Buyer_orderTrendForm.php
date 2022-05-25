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

use common\library\Def;
use common\library\Timezone;

/**
 * @Id Buyer_orderTrendForm.php 2018.10.29 $
 * @author mosir
 */
class Buyer_orderTrendForm extends Model
{
	public $errors = null;
	
	public function formData($post = null) 
	{
		list($curMonthAmount, $curMonthQuantity, $curDays, $beginMonth, $endMonth) = $this->getMonthTrend(Timezone::gmtime());
		list($preMonthAmount, $preMonthQuantity, $preDays) = $this->getMonthTrend($beginMonth - 1);
		
		$series = array($curMonthAmount, $preMonthAmount);
		$legend = array('本月消费额','上月消费额');
		
		$days = $curDays > $preDays ? $curDays : $preDays;
		
		// 获取日期列表
		$xaxis = array();
		for($day = 1; $day <= $days; $day++) {
			$xaxis[] = $day.'日';
		}

		$result = array(
			'id'		=>  mt_rand(),
			'theme' 	=> 'macarons',
			'width'		=> 880,
			'height'    => 260,
			'option'  	=> json_encode([
				'grid' => ['left' => '0', 'right' => 0, 'top' => '40', 'bottom' => '10', 'containLabel' => true],
				'tooltip' 	=> ['trigger' => 'axis'],
				'legend'	=> [
					'data' => $legend
				],
				'calculable' => true,
   				'xAxis' => [
        			[
						'type' => 'category', 
						'data' => $xaxis
        			]
    			],
				'yAxis' => [
        			[
            			'type' => 'value'
        			]
   				 ],
				 'series' => [
					[
						'name' => $legend[0],
						'type' => 'bar',
						'data' => $series[0],
					],
					[
						'name' => $legend[1],
						'type' => 'bar',
						'data' => $series[1],
					]
				]
			])
		);
		
		return $result;
	}
	
	/* 月数据统计 */
	private function getMonthTrend($month = 0)
	{
		// 本月
		if(!$month) $month = Timezone::gmtime();
		
		// 获取当月的开始时间戳和结束那天的时间戳
		list($beginMonth, $endMonth) = Timezone::getMonthDay(Timezone::localDate('Y-m', $month));
		
		$list = DepositTradeModel::find()->select('amount,end_time')->where(['bizIdentity' => Def::TRADE_ORDER, 'status' => 'SUCCESS'])->andWhere(['>=', 'end_time', $beginMonth])->andWhere(['<=', 'end_time', $endMonth])->andWhere(['buyer_id' => Yii::$app->user->id])->asArray()->all();
		
		// 该月有多少天
		$days = round(($endMonth-$beginMonth) / (24 * 3600));
		
		// 按天算归类
		$amount = $quantity = array();
		foreach($list as $key => $val)
		{
			$day = Timezone::localDate('d', $val['end_time']);
	
			if(isset($amount[$day-1])) {
				$amount[$day-1] += $val['amount'];
				$quantity[$day-1]++;
			}
			else {
				$amount[$day-1] = $val['amount'];
				$quantity[$day-1] = 1;
			}
		}
		
		// 给天数补全
		for($day = 1; $day <= $days; $day++)
		{
			if(!isset($amount[$day-1])) {
				$amount[$day-1] = 0;
				$quantity[$day-1] = 0;
			}
		}
		// 按日期顺序排序
		ksort($amount);
		ksort($quantity);

		return array($amount, $quantity, $days, $beginMonth, $endMonth);
	}
}
