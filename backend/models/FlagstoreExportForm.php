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
 * @Id FlagstoreExportForm.php 2018.8.18 $
 * @author mosir
 */
class FlagstoreExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'store_name' 	=> '店铺名称',
    		'brand_name' 	=> '相关联的品牌',
    		'cate_name' 	=> '相关联的分类',
    		'keyword' 		=> '关键字',
    		'status' 		=> '开启',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'FLAGSTORE_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if($k == 'status') {
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
