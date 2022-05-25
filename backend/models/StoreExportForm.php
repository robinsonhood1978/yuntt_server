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

use common\models\SgradeModel;

use common\library\Timezone;
use common\library\Language;
use common\library\Def;

/**
 * @Id StoreExportForm.php 2018.8.10 $
 * @author mosir
 */
class StoreExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'store_id'		=> 'ID',
			'store_name'	=> '店铺名称',
			'stype'			=> '主体类型',
    		'username' 		=> '用户名',
    		'owner_name' 	=> '店主姓名',
			'tel'			=> '联系电话',
    		'region_name' 	=> '所在地区',
    		'sgrade' 		=> '店铺等级',
			'recommended'   => '推荐',
			'state'			=> '状态',
			'add_time'  	=> '添加时间'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'STORE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if(in_array($k, ['add_time'])) {
					$value[$k] = Timezone::localDate('Y-m-d H:i:s', $value[$k]);
				} elseif($k == 'sgrade') {
					$value[$k] = self::getSgrade($value[$k]);
				} elseif($k == 'state') {
					$value[$k] = self::getStatus($value[$k]);
				} elseif($k == 'recommended') {
					$value[$k] = $value[$k] == 1 ? Language::get('yes') : Language::get('no');
				} elseif($k == 'stype') {
					$value[$k] = $value[$k] == 'company' ? Language::get('company') : Language::get('personal');
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
		if($status === null) {
			return $result;	
		}

		return isset($result[$status]) ? $result[$status] : '';		
	}
	
	private static function getSgrade($grade_id = 0)
	{
		$sgrades = SgradeModel::getOptions();
		return isset($sgrades[$grade_id]) ? $sgrades[$grade_id] : '';
	}	
}
