<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_integral_hot;

use Yii;

use common\models\GoodsModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */

class Df_integral_hotWidget extends BaseWidget
{
    var $name = 'df_integral_hot';

    public function getData()
    {
		return array(
			'news' => $this->getList(),
			'hots' => $this->getList('hot'),
		);
    }
	public function getList($param = '', $num = 10)
	{
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.default_image,g.goods_name,g.price,gst.sales,gi.max_exchange')->joinWith('goodsStatistics gst', false)->joinWith('goodsIntegral gi', false);
		if($param = 'hot') {
			$query->orderBy(['gst.sales'  => SORT_DESC]);
		} else $query->orderBy(['g.add_time' => SORT_DESC]);
		
		$list = $query->limit($num)->asArray()->all();
		foreach($list as $key => $goods){
			empty($goods['default_image']) && $list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
		} 	
		return $list;
	}
}