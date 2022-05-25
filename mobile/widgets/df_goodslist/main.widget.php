<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\widgets\df_goodslist;

use Yii;

use common\models\GoodsModel;
use common\models\GcategoryModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_goodslistWidget extends BaseWidget
{
    var $name = 'df_goodslist';

    public function getData()
    {
        $query = GoodsModel::find()->alias('g')->select('g.goods_id,goods_name,price,mkPrice,default_image,gst.sales')
            ->joinWith('goodsStatistics gst', false)
            ->where(['if_show' => 1, 'closed' => 0]);
       
        if($this->options['source'] == 'choice') {
            $items = explode(',', $this->options['items']);
            $query->andWhere(['in', 'g.goods_id', $items]);
            $this->options['quantity'] = count($items);
        } else {
            if($this->options['source'] == 'category') {
                $query->andWhere(['in', 'cate_id', GcategoryModel::getDescendantIds($this->options['cate_id'])]);
            }
        }
        if($this->options['paging'] == 1) {
            $query->limit($this->options['page_size'] > 0 ? $this->options['page_size'] : 4);
        } else {
            $query->limit($this->options['quantity'] > 0 ? $this->options['quantity'] : 4);
        }

        if($this->options['orderby']) {
            $orderBy = explode('|', $this->options['orderby']);
            $query->orderBy([$orderBy[0]  => $orderBy[1] == 'desc' ? SORT_DESC : SORT_ASC]);
        } else {
            $query->orderBy(['g.goods_id' => SORT_DESC]);
        }

        if(empty($list = $query->asArray()->all())) {
            $list = array([],[],[],[]);
        }

        return array_merge(['list' => $list], $this->options);
    }

    public function parseConfig($input)
    {
        return $input;
    }   
}
