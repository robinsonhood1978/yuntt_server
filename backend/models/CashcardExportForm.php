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

use common\models\UserModel;

use common\library\Language;
use common\library\Timezone;

/*
 * @Id CashcardExportForm.php 2018.8.3 $
 * @author mosir
 */
class CashcardExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'id'			=> 'ID',
			'name' 			=> '卡名称',
			'cardNo'		=> '卡号',
			'password'		=> '密码',
			'money'			=> '卡金额',
			'add_time' 		=> '生成时间',
			'expire_time' 	=> '过期时间',
			'printed'		=> '制卡状态',
			'username'		=> '使用者',
			'active_time'	=> '激活时间',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'Cashcard_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['add_time', 'expire_time', 'active_time'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if($k == 'printed') {
					$value[$k] = $value[$k] == 0 ? Language::get('no_print') : Language::get('printed');
				}
				if($k == 'username') {
					$value[$k] = UserModel::find()->select('username')->where(['userid' => $value['useId']])->scalar();
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
