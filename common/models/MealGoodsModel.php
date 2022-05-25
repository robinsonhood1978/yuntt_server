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

use common\models\MealModel;

use common\library\Basewind;

/**
 * @Id MealGoodsModel.php 2018.5.24 $
 * @author mosir
 */


class MealGoodsModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%meal_goods}}';
    }
	
	// 关联表
	public function getMeal()
	{
		return parent::hasOne(MealModel::className(), ['meal_id' => 'meal_id']);
	}
	// 关联表
	public function getGoods()
	{
		return parent::hasOne(GoodsModel::className(), ['goods_id' => 'goods_id']);
	}
	
	public static function getMealGoods($goods_id = 0, $cached = true)
    {
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached) 
		{
			$goodsInMeals = parent::find()->alias('mg')->joinWith('meal')
				->select('mg.meal_id')->where(['status' => 1, 'goods_id' => $goods_id])->column();
			
			$meals = MealModel::find()->select('meal_id,price,title')->with(['mealGoods' => function($query){
				$query->alias('mg')->select('mg.mg_id,mg.meal_id,mg.goods_id,g.goods_name,g.price,g.default_image')->joinWith('goods g', false);
			}])->where(['in', 'meal_id', $goodsInMeals])->asArray()->all();
			
			foreach($meals as $key => $meal)
			{
				$priceTotal = 0;
				if(isset($meal['mealGoods']) && !empty($meal['mealGoods']))
				{
					foreach($meal['mealGoods'] as $k => $v) {
						$priceTotal += $v['price'];
						if($goods_id == $v['goods_id']) {
							unset($meals[$key]['mealGoods'][$k]);
						}
					}
				}
				$meals[$key]['priceTotal'] = $priceTotal;
				$meals[$key]['savePrice'] = $priceTotal - $meal['price'];
				
				// 方便PC端页面控制宽度
				if(Basewind::getCurrentApp() == 'pc') {
					$meals[$key]['width'] = count($meals[$key]['mealGoods']) * 165;
				}
			}
			
			$data = $meals;
			$cache->set($cachekey, $data, 3600);
		}
		return $data;
	}
}
