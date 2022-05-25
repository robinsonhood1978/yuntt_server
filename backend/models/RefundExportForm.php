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

use common\library\Language;
use common\library\Timezone;

/**
 * @Id RefundExportForm.php 2018.8.29 $
 * @author mosir
 */
class RefundExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'refund_id'				=> 'ID',
			'refund_sn' 			=> '退款编号',
    		'buyer_name' 			=> '买家',
    		'store_name' 			=> '卖家',
			'total_fee' 			=> '交易金额',
			'refund_total_fee' 		=> '退款金额',
			'created' 				=> '申请时间',
			'status'				=> '退款状态',
			'refund_reason'			=> '退款原因',
			'shipped'				=> '收货情况',
			'intervene' 			=> '平台介入',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'REFUND_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			$quantity++;
			$amount += floatval($value['refund_total_fee']);

			foreach($lang_bill as $k => $v) {
				if(in_array($k, ['created'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if(in_array($k, ['intervene'])) {
					$value[$k] = $value[$k] == 1 ? Language::get('yes') : Language::get('no');
				}
				if(in_array($k, ['status'])) {
					$value[$k] = Language::get('REFUND_'.strtoupper($value[$k]));
				}
				if(in_array($k, ['shipped'])) {
					$value[$k] = Language::get('shipped_'.$value[$k]);
				}
	
				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}
        	$record_xls[] = $record_value;
    	}

		$record_xls[] = array('refund_sn' => '');// empty line
		$record_xls[] = array('refund_sn' => sprintf('退款总数：%s笔，退款总额：%s元', $quantity, $amount));
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
