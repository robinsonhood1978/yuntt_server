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
 * @Id WebimLogModel.php 2018.8.26 $
 * @author mosir
 */

class WebimLogModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%webim_log}}';
    }

	// 关联表
	public function getFromUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'fromid']);
	}
	// 关联表
	public function getToUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'toid']);
	}
}
