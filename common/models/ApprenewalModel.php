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
 * @Id ApprenewalModel.php 2018.5.7 $
 * @author mosir
 */

class ApprenewalModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%apprenewal}}';
    }
	
	// 关联表
	public function getAppmarket()
	{
		return parent::hasOne(AppmarketModel::className(), ['appid' => 'appid']);
	}
	
	/* 判断是购买还是续费 */
	public static function checkIsRenewal($appid = '', $userid = 0)
	{
		$result = false;
		if($appid && $userid) 
		{
			$query = parent::find()->select('rid,expired')->where(['userid' => $userid, 'appid' => $appid])->orderBy(['rid' => SORT_DESC])->one();
			if($query && ($query->expired > Timezone::gmtime())) {
				$result = $query;
			}
		}
		return $result;
	}
}
