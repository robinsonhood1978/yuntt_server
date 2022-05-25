<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;

use common\models\DepositTradeModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id DepositTradeExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositTradeExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'add_time' 		=> '创建时间',
    		'bizOrderId' 	=> '商户订单号',
    		'tradeNo' 		=> '交易号',
			'title' 		=> '交易标题',
			'buyer_name'	=> '交易方',
			'party' 		=> '对方',
			'amount' 		=> '金额（元）',
			'status' 		=> '状态',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'TRADE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['add_time']	= Timezone::localDate('Y/m/d H:i:s',$val['add_time']);
			$record_value['bizOrderId']	= $val['bizOrderId'];
			$record_value['tradeNo']	= $val['tradeNo'];
			$record_value['title']		= $val['title'];
			$record_value['buyer_name']	= $val['real_name'] ? $val['real_name'] : $val['account'];
			
			$partyInfo = DepositTradeModel::getPartyInfoByRecord($val['buyer_id'], $val);
			$record_value['party']		= $partyInfo['name'];
			
			$record_value['amount']		= $val['flow'] == 'income' ? '+'.$val['amount'] : '-'.$val['amount']; 
			$record_value['status']		= Language::get(strtolower($val['status']));
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
