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

/**
 * @Id ManagerExportForm.php 2018.7.30 $
 * @author mosir
 */
class ManagerExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'userid'		=> 'ID',
			'username' 		=> '用户名',
    		'email' 		=> '电子邮箱',
			'phone_mob' 	=> '手机号码',
    		'im_qq' 		=> 'QQ',
    		'create_time' 	=> '注册时间',
			'last_login' 	=> '最后登录时间',
			'last_ip' 		=> '最后登录IP',
    		'logins' 		=> '登录次数',
		);
		$record_xls[] = array_values($lang_bill);
		$folder ='ADMIN_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['create_time', 'last_login'])) {
					$value[$k] = Timezone::localDate('Y-m-d H:i:s', $value[$k]);
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
