<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;
use yii\helpers\ArrayHelper;

/**
 * @Id Language.php 2018.3.3 $
 * @author mosir
 */
 
class Language
{
	public static $public = 'common';
	
    public static function get($message, $category = '')
    {
		$word = '';
		
		if($category) {
			$word = Yii::t($category, $message);
		} else {
			if(Yii::$app->controller) $word = Yii::t(Yii::$app->controller->id, $message);// need this line in action
		}
	
		// search form common file
		if(in_array($word, ['', $message])) {
			$word = Yii::t(self::$public, $message);
		}
		if(empty($word)) $word = $message;
		
		return $word;
    }
	
	public static function find($category = '')
	{
		$result = [];
		if(file_exists($file = Yii::getAlias('@app') . '/languages/'.Yii::$app->language.'/'.self::$public.'.php')) {
			$result = ArrayHelper::merge($result, require($file));
		}
		if(file_exists($file = Yii::getAlias('@app') . '/languages/'.Yii::$app->language.'/'.$category.'.php')) {
			$result = ArrayHelper::merge($result, require($file));
		}
		
		return $result;
	}
}