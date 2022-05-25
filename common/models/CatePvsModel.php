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

use common\models\GoodsPropModel;
use common\models\GoodsPropValueModel;
use common\models\GoodsPvsModel;

/**
 * @Id CatePvsModel.php 2018.5.5 $
 * @author mosir
 */

class CatePvsModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%cate_pvs}}';
    }
	
	/* 获取分类的属性
	 * @param $cate_id int
	 * @param $goods_id int 传goods_id表示：将该商品具有的属性设置选中状态
	 */
	public static function getCatePvs($cate_id, $goods_id = 0)
	{
		$result = $values = array();
		
		$goodsPvs = [];
		if($goods_id > 0) {
			if(($query = GoodsPvsModel::find()->where(['goods_id' => $goods_id])->one())) {
				$goodsPvs = explode(';', $query->pvs);
			}
		}
		
		if(($query = parent::find()->where(['cate_id' => $cate_id])->one()))
		{
			$catePvs = $query->pvs;
			foreach(explode(';', $catePvs) as $key => $val)
			{
				if(empty($val)) continue;
				$item = explode(':', $val);
				
				/* 检验属性名和属性值是否存在 */
				if(($props = GoodsPropModel::find()->where(['pid' => $item[0], 'status' => 1])->asArray()->one())) {
					if(($propValue = GoodsPropValueModel::find()->where(['pid' => $item[0], 'vid' => $item[1], 'status' => 1])->asArray()->one())) {
						if($goodsPvs && in_array($val, $goodsPvs)) $propValue['selected'] = 1;
						
						$result[$item[0]] = $props;
						$values[$item[0]][] = $propValue;
						$result[$item[0]]['value'] = $values[$item[0]];
					}
				}
			}
		}
		return $result;
	}
}
