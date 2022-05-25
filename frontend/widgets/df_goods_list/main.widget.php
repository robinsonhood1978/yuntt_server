<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_goods_list;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_goods_listWidget extends BaseWidget
{
    var $name = 'df_goods_list';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$num = intval($this->options['num']) > 0 ? intval($this->options['num']) : 30;

            $data = array(
				'model_id'	 => mt_rand(),
				'model_name' => $this->options['model_name'],
            	'goods_list' => $this->getRecommendGoods(intval($this->options['img_recom_id']), $num, true, intval($this->options['img_cate_id']))
			);
			$cache->set($key, $data, $this->ttl);
        }
		
        return $data;	
    }

 	public function getConfigDataSrc()
    {
		// 取得推荐类型
		$this->params['recommends'] = $this->getRecommendOptions(true);
		
        // 取得一级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, 0);
    }	
}
