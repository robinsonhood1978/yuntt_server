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
use yii\redis\ActiveRecord;

/**
 * @Id AcategoryRedisModel.php 2018.8.22 $
 * @author mosir
 */


 /*
 	删除redis中的所有数据
    Yii::$app->redis->flushall();
 */
class AcategoryRedisModel extends ActiveRecord
{
    /**
	 * 主键 默认为 id
	 *
	 * @return array|string[]
	 */
	public static function primaryKey()
	{
		return AcategoryModel::primaryKey();
	}

	/**
	 * 模型对应记录的属性列表
	 *
	 * @return array
	 */
	public function attributes()
	{
		$model = new AcategoryModel();
		return $model->attributes();
	}
}
