<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\widgets\df_teambuy;

use Yii;

use common\models\TeambuyModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_teambuyWidget extends BaseWidget
{
    var $name = 'df_teambuy';

    public function getData()
    {
        $query = TeambuyModel::find()->alias('tb')->select('tb.id,tb.specs,tb.status,tb.goods_id,tb.people,g.default_image,g.price,g.goods_name,g.default_spec as spec_id,s.store_name,gst.sales')
			->joinWith('goods g', false, 'INNER JOIN')
            ->joinWith('store s', false)
            ->joinWith('goodsStatistics gst', false)
			->where(['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0])
            ->orderBy(['id' => SORT_DESC]);

        if($this->options['source'] == 'choice') {
            $query->andWhere(['in', 'g.goods_id', explode(',', $this->options['items'])]);
        } else {
            $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 3);
        }
       
        if(empty($list = $query->asArray()->all())) {
            $list = array([],[],[]);
        }

        foreach($list as $key => $value) {
            $list[$key]['teamPrice'] = $this->getTeamPrice($value['spec_id'], $value['specs'], $value['price']);
        }
       
        return array_merge(['list' => $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    } 
    
    /**
	 * 计算拼团价格
	 */
	private function getTeamPrice($spec_id, $specs = array(), $price = 0) {
		if(!is_array($specs)) {
			$specs = unserialize($specs);
		}
        
		if(!isset($specs[$spec_id])) {
			return $price;
		}
		return round($price * $specs[$spec_id]['price'] / 1000, 4) * 100;
	}
}
