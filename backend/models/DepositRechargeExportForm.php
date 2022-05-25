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

/**
 * @Id DepositRechargeExportForm.php 2018.8.3 $
 * @author mosir
 */
class DepositRechargeExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'add_time' 		=> '创建时间',
			'tradeNo' 		=> '交易号',
    		'orderId' 		=> '商户订单号',
			'username' 		=> '用户名',
			'amount' 		=> '充值金额',
			'status' 		=> '状态',
			'reamrk'		=> '充值备注',
			'examine' 		=> '操作员',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'RECHARGE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['add_time'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if($k == 'status') {
					$value[$k] = Language::get(strtolower($value[$k]));
				}

				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}
			$record_value['username'] = UserModel::find()->select('username')->where(['userid' => $value['userid']])->scalar();
	
        	$record_xls[] = $record_value;
    	}
		
		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
