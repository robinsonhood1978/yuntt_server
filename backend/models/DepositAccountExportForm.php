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
 * @Id DepositAccountExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositAccountExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'account' 		=> 	'账户名',
			'username' 		=> 	'用户名',
    		'real_name'		=> 	'真实姓名',
			'money' 		=> 	'可用金额',
			'frozen' 		=> 	'冻结金额',
    		'pay_status' 	=> 	'开启余额支付',
			'add_time' 		=> 	'创建时间',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'ACCOUNT_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['add_time'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if($k == 'pay_status') {
					$value[$k] = $value[$k] == 'ON' ? Language::get('yes') : Language::get('no');
				}

				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
