<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

/**
 * @Id SgradeModel.php 2018.4.3 $
 * @author mosir
 */


class SgradeModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%sgrade}}';
    }
	
	public static function getOptions($cached = false)
    {
        $cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			$data = parent::find()->select('grade_name')->indexBy('grade_id')->column();
            $cache->set($cachekey, $data, 3600); 
        }
        return $data;
    }
}
