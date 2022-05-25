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
 * @Id SgradeExportForm.php 2018.8.17 $
 * @author mosir
 */
class SgradeExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'grade_id' 		=> 'ID',
    		'grade_name' 	=> '等级名称',
    		'goods_limit' 	=> '允许发布商品数',
			'space_limit'	=> '允许上传空间大小（MB）',
    		'charge'		=> '收费标准',
    		'need_confirm'	=> '是否需要审核',
			'sort_order'	=> '排序'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'SGRADE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) 
			{
				if($k == 'need_confirm') {
					$value[$k] = $value[$k] == 1 ? Language::get('yes') : Language::get('no');
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
