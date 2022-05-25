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

use common\models\GcategoryModel;

/**
 * @Id GuideshopModel.php 2020.2.22 $
 * @author mosir
 */

class GuideshopModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%guideshop}}';
    }

    // 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}

    /**
     * 获取支持社区团购商品类目ID
     * @param boolean $querychild 是否获取下级ID
     */
    public static function getCategoryId($querychild = false, $selfin = true)
    {
		$cateId = intval(Yii::$app->params['guideshop']['cateId']);

        if(!$querychild) {
            return $cateId;
        }

        if($cateId) {
			return GcategoryModel::getDescendantIds($cateId, 0, true, true, $selfin);
        }
		
        return false;
    }

    /**
     * 给团长分成
     */
    public static function distributeProfit($order = array())
    {
        if($order['otype'] != 'guidebuy') {
            return false;
        }

        // 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入 
		$depopay_type = \common\library\Business::getInstance('depopay')->build('guidebuy');
		$result = $depopay_type->distribute($order);
		
		if($result !== true) {
			return false;
		}
		return true;
    }
}
