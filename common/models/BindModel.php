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
 * @Id BindModel.php 2018.6.1 $
 * @author mosir
 */

class BindModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%bind}}';
    }
	
	public static function bindUser($bind = null, $userid = 0)
	{
		// APP中微信登录兼容处理(for conditions : openid => $bind->unionid)
		if(!($model = parent::find()->where(['code' => $bind->code])->andWhere(['or', ['unionid' => $bind->unionid], ['openid' => $bind->unionid]])->one())) {
			$model = new BindModel();
		}
		$model->unionid 	= $bind->unionid;
		$model->openid  	= $bind->openid ? $bind->openid : ''; // 只有微信才有openid
		$model->token   	= $bind->access_token;
		$model->userid		= $userid;
		$model->nickname	= $bind->nickname ? $bind->nickname : '';
		$model->code     	= $bind->code;
		$model->enabled 	= 1;
				
		if(!$model->save()) {
			return false;
		}
		return true;
	}

	public static function getOpenid($userid = 0)
	{
		$openid = '';
		if($model = parent::find()->where(['userid' => $userid])->one()) {
			$openid = $model->openid;
		}
		return $openid;
	}
}
