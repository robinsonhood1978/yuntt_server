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

use common\library\Basewind;
use common\library\Page;

/**
 * @Id MealModel.php 2018.3.23 $
 * @author mosir
 */


class MealModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%meal}}';
    }
	// 关联表
	public function getMealGoods()
	{
		return parent::hasMany(MealGoodsModel::className(), ['meal_id' => 'meal_id']);
	}
	// 关联表
	public function getStore()
	{
		return parent::hasMany(StoreModel::className(), ['store_id' => 'store_id']);
	}
	
	public static function getList($appid, $store_id = 0, $params = array(), &$pagination = false)
	{
		$query = parent::find()->where(['store_id' => $store_id]);	
		if($params['where']) $query->andWhere($params['where']);
		if($params['getMealGoods']) $query->with('mealGoods');
		
		if($pagination !== false && $pagination['pageSize'] > 0) {
			$page = Page::getPage($query->count(), $pagination['pageSize']);
			$query->offset($page->offset)->limit($page->limit);
			$pagination = $page;
		}
		return $query->orderBy(['meal_id' => SORT_DESC])->asArray()->all();
	}
}
