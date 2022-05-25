<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\library;

use yii;
use yii\helpers\Url;

use common\library\Page;

/**
 * @Id Formatter.php 2018.10.6 $
 * @author yxyc
 */
 
class Formatter
{
	/*
	 * 格式化图片路径为绝对路径
	 */
	public static function path($image, $defaultKey = null, $defaultValue = '')
	{
		if(!$image || (Url::isRelative($image) && !file_exists(Yii::getAlias('@frontend'). '/web/'.$image))) {
			if($defaultKey == 'goods') {
				$image = Yii::$app->params['default_goods_image'];
			} elseif($defaultKey == 'portrait') {
				$image = Yii::$app->params['default_user_portrait'];
			} elseif($defaultKey == 'store') {
				$image = Yii::$app->params['default_store_logo'];
			} else $image = $defaultValue;
		}
		
		return Page::urlFormat($image);
	}
}