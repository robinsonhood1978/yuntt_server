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
 * @Id FriendModel.php 2018.3.20 $
 * @author mosir
 */

class FriendModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%friend}}';
    }
	
	// 关联表
	public function getUserFriend()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'friend_id']);
	}
}
