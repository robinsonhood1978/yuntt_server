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

use common\library\Language;
use common\library\Timezone;

/**
 * @Id AppbuylogModel.php 2018.7.18 $
 * @author mosir
 */

class AppbuylogModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%appbuylog}}';
    }
	
	// 关联表
	public function getAppmarket()
	{
		return parent::hasOne(AppmarketModel::className(), ['appid' => 'appid']);
	}
	
	/* 获取订单的支付标题 */
	public static function getSubjectOfPay($params = array())
	{
		$subject = sprintf(Language::get('subject_for_payapp'), Language::get($params['appid']), $params['period']);
		return addslashes($subject);
	}
	
	public static function genOrderId( $length = 12)
    {
        // 选择一个随机的方案
        mt_srand((double) microtime() * 1000000);
		
		if($length > 0) {
			$orderId = self::makeChar( $length );
		} else {
        	$orderId = Timezone::localDate('YmdHis', Timezone::gmtime()) . str_pad(mt_rand(1, 99), 2, '0', STR_PAD_LEFT).mt_rand(1000, 9999);
		}
        if (!parent::find()->where(['orderId' => $orderId])->exists()) {
            return $orderId;
        }
        // 如果有重复的，则重新生成
        return self::genOrderId( $length );
    }
	
	/* 生成指定长度的随机字符串 */
	private static function makeChar( $length = 12 )
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
}
