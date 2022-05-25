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
 * @Id CashcardModel.php 2018.4.17 $
 * @author mosir
 */

class CashcardModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cashcard}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'useId']);
	}

	// 关联表
	public function getDepositTrade() {
		return parent::hasOne(DepositTradeModel::className(), ['bizOrderId' => 'cardNo']);
	}
	
	public static function genCardNo( $length = 0)
    {
        // 选择一个随机的方案
        mt_srand((double) microtime() * 1000000);
		
		if($length > 0) {
			$cardNo = self::makeChar( $length );
		} else {
        	$cardNo = Timezone::localDate('YmdHis', Timezone::gmtime()) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT).mt_rand(1000, 9999);
		}
        if (!parent::find()->select('cardNo')->where(['cardNo' => $cardNo])->exists()) {
            return $cardNo;
        }
        // 如果有重复的，则重新生成
        return self::genCardNo( $length );
    }
	
	/* 生成指定长度的随机字符串 */
	private static function makeChar( $length = 8 )
	{  
		// 密码字符集，可任意添加你需要的字符  
		$chars = array('0', '1', '2', '3', '4', '5', '6', '7', '8', '9');  

		$str = '';
		for($i = 0; $i < $length; $i++){  
   			$str .= $chars[array_rand($chars)];
		}
		if(substr( $str, 0, 1 ) == '0') {
			$str = self::makeChar( $length );
		}

		return $str;
	}
	
	public static function validateCard($cardNo = null, $password = null)
	{
		if(!$cardNo || !$password) return false;
		
		if(parent::find()->select('id')->where(['cardNo' => $cardNo, 'password' => $password])->one()) {
			return true;
		}
		return false;
	}
}
