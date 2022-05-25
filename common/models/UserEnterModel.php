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

use common\library\Timezone;

/**
 * @Id UserEnterModel.php 2018.7.30 $
 * @author mosir
 */

class UserEnterModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%user_enter}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}
	
	public static function enter($identity = null, $scene = 'backend')
	{
		$model = new UserEnterModel();
		$model->userid = $identity->userid;
		$model->username = $identity->username;
		$model->scene = $scene;
		$model->ip = Yii::$app->request->userIP;
		$model->add_time = Timezone::gmtime();
		
		return $model->save();
	}
}
