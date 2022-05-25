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
 * @Id NaviationModel.php 2018.3.17 $
 * @author mosir
 */


class NavigationModel extends ActiveRecord
{
    /**
     * @return string AR 类关联的数据库表名称
	 * 可省略，但类名和表明要一致
     */
    public static function tableName()
    {
        return '{{%navigation}}';
    }
	
	public static function getList($type = 'middle', $cached = true)
	{
		$type = empty($type) ? 'middle' : $type;
		
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{ 
			$data = [$type => parent::find()->where(['type' => $type, 'if_show' => 1])->orderBy(['sort_order' => SORT_ASC])->asArray()->all()];
			
			//第二个参数即是我们要缓存的数据 
    		//第三个参数是缓存时间，如果是0，意味着永久缓存。默认是0 
    		$cache->set($cachekey, $data, 3600); 
		}
		
		return $data;
	}
}