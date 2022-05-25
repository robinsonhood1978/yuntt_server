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
 * @Id CouponsnModel.php 2018.5.20 $
 * @author mosir
 */

class CouponsnModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%coupon_sn}}';
    }
	// 关联表
	public function getCoupon()
	{
		return parent::hasOne(CouponModel::className(), ['coupon_id' => 'coupon_id']);
	}
	
	public static function createRandom( $length = 8 )
	{
		$sn = self::makeChar( $length );
		if(!parent::find()->where(['coupon_sn' => $sn])->exists()) {
			return $sn;
		}
		return self::createRandom($length);
	}
	
	private static function makeChar( $length = 8 )
	{  
		// 密码字符集，可任意添加你需要的字符  
		$chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');  

		// 在 $chars 中随机取 $length 个数组元素键名  
		$str = '';  
		for($i = 0; $i < $length; $i++){  

   			// 将 $length 个数组元素连接成字符串  
   			$str .= $chars[array_rand($chars)];
		}
		
		//if(substr( $str, 0, 1 ) == '0') {
			//$str = self::makeChar( $length );
		//}

		return $str;
	}
}
