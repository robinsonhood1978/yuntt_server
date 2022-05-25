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
use common\library\Language;

/**
 * @Id MsgLogExportForm.php 2018.8.24 $
 * @author mosir
 */
class MsgLogExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{		
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'msguser' 	=> '短信用户',
    		'receiver'  => '接收者手机号',
    		'content' 	=> '短信内容',
    		'quantity' 	=> '数量',
    		'add_time' 	=> '时间',
			'status'	=> '状态',
			'message'	=> '说明',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'MSGLOG_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($lang_bill as $key => $val) 
		{
			$record_value[$key] = '';
		}

		foreach($list as $key => $val)
    	{
			$record_value['msguser'] 	= $val['username'] ? $val['username'] : Language::get('system');
			$record_value['receiver']	= $val['receiver'];
			$record_value['content']	= $val['content'];
			$record_value['quantity']	= $val['quantity'];
			$record_value['add_time']	= Timezone::localDate('Y-m-d H:i:s', $val['add_time']);
			$record_value['status'] 	= $val['status'] ? Language::get('send_success') : Language::get('send_failed');
			$record_value['message'] 	= $val['message'];
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
