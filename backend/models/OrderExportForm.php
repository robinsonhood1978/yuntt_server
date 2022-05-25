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

use common\library\Timezone;
use common\library\Def;

/**
 * @Id OrderExportForm.php 2018.8.2 $
 * @author mosir
 */
class OrderExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'order_id'		=> 'ID',
			'order_sn' 		=> '订单编号',
			'store_name' 	=> '店铺名称',
    		'buyer_name' 	=> '买家',
    		'order_amount' 	=> '订单总额',
    		'payment_name' 	=> '付款方式',
			'status'		=> '订单状态',
			'add_time' 		=> '下单时间',
			'pay_time' 		=> '付款时间',
			'ship_time' 	=> '发货时间',
			'finished_time'	=> '完成时间',
			'consignee' 	=> '收货人姓名',
    		'address' 		=> '收货人地址',
			'phone_mob' 	=> '收货人电话',
			'pay_message'	=> '买家留言',
			'express_no'	=> '快递单号',
			'postscript'	=> '备注',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'ORDER_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$amount = $quantity = 0;
		$record_value = array();
		foreach($list as $key => $value)
    	{
			$quantity++;
			$amount += floatval($value['order_amount']);

			foreach($lang_bill as $k => $v) {
				if(in_array($k, ['add_time', 'pay_time', 'ship_time', 'finished_time'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if($k == 'address') {
					$value[$k] = $value['region_name'] . $value[$k];
				}
				if($k == 'status') {
					$value[$k] = Def::getOrderStatus($value['status']);
				}
	
				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}
        	$record_xls[] = $record_value;
    	}

		$record_xls[] = array('seller_name' => '');// empty line
		$record_xls[] = array('seller_name' => sprintf('订单总数：%s笔，订单总额：%s元', $quantity, $amount));
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
