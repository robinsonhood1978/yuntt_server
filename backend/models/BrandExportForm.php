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
use common\library\Page;

/**
 * @Id BrandExportForm.php 2018.8.9 $
 * @author mosir
 */
class BrandExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'brand_id' 		=> '品牌ID',
    		'brand_name' 	=> '品牌名称',
    		'brand_logo' 	=> '品牌标识',
			'letter'		=> '首字母',
    		'recommended' 	=> '推荐',
			'if_show'		=> '显示',
    		'tag' 			=> '品牌标签'
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'BRAND_'.Timezone::localDate('Ymdhis', Timezone::gmtime());

		$record_value = array();
		foreach($list as $key => $value)
    	{
			foreach($lang_bill as $k => $v) {

				if($k == 'brand_logo') {
					$value[$k] = Page::urlFormat($value['brand_logo']);
				}
				if(in_array($k, ['if_show', 'recommended'])) {
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
