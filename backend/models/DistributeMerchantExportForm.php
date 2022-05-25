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

use common\library\Timezone;
use common\library\Language;
use common\library\Def;

/**
 * @Id DistributeMerchantExportForm.php 2020.2.10 $
 * @author mosir
 */
class DistributeMerchantExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'dmid'			=> 'ID',
    		'username' 		=> '用户名',
			'phone_mob'		=> '联系电话',
			'parent'		=> '邀请人',
			'name'			=> '分销店名',
			'status'		=> '状态',
			'created'  	=> '申请时间'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'DISTRIBUTEMERCHAT_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['created'])) {
					$value[$k] = Timezone::localDate('Y/m/d H:i:s', $value[$k]);
				}
				if($k == 'status') {
					$value[$k] = self::getStatus($value[$k]);
				}

				if($k == 'parent') {
					$value[$k] = UserModel::find()->select('username')->where(['userid' => $value[$k]])->scalar();
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
	
	private static function getStatus($status = null)
	{
		$result = array(
            Def::STORE_APPLYING  => Language::get('applying'),
			Def::STORE_NOPASS	 => Language::get('nopass'),
            Def::STORE_OPEN      => Language::get('open'),
            Def::STORE_CLOSED    => Language::get('close'),
        );
		if($status !== null) {
			return isset($result[$status]) ? $result[$status] : '';
		}
		return $result;		
	}
}
