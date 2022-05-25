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

use common\models\GcategoryModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id GoodsExportForm.php 2018.8.9 $
 * @author mosir
 */
class GoodsExportForm extends Model
{
	public $errors = null;
	
	public static function download($list)
	{
		// 文件数组
		$record_xls = array();		
		$lang_bill = array(
			'goods_id' 		=> 	'ID',
			'goods_name' 	=> 	'商品名称',
    		'price' 		=> 	'价格',
    		'store_name' 	=> 	'店铺名称',
			'brand' 		=>  '品牌',
    		'cate_name' 	=> 	'所属分类',
    		'if_show' 		=> 	'上架',
    		'closed' 		=> 	'禁售',
			'views'  		=>  '浏览数',
		);
		$record_xls[] = array_values($lang_bill);
		$folder = 'GOODS_'.Timezone::localDate('Ymdhis', Timezone::gmtime());
		
		$amount = $quantity = 0;
		$record_value = array();
		foreach($list as $key => $value)
    	{
			$quantity++;
			$amount += floatval($value['price']);

			foreach($lang_bill as $k => $v) {
				if($k == 'cate_name') {
					$value[$k] = GcategoryModel::formatCateName($value[$k], false, ' / ');
				}
				if(in_array($k, ['if_show', 'closed'])) {
					$value[$k] = $value[$k] == 1 ? Language::get('yes') : Language::get('no');
				}
	
				$record_value[$k] = $value[$k] ? $value[$k] : '';
			}
        	$record_xls[] = $record_value;
    	}
		$record_xls[] = array('goods_name' => '');// empty line
		$record_xls[] = array('goods_name' => sprintf('商品总数：%s笔，商品总额：%s元', $quantity, $amount));

		return \common\library\Page::export([
			'models' 	=> $record_xls, 
			'fileName' 	=> $folder,
		]);
	}
}
