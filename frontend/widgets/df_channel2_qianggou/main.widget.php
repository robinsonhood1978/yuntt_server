<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_qianggou;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_qianggouWidget extends BaseWidget
{
    var $name = 'df_channel2_qianggou';

    public function getData()
    {
		$cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$data = array(
				'model_id'		=> mt_rand(),
				'model_name'	=> $this->options['model_name'],
				'floor_title'	=> $this->options['floor_title'],
				'sub_title'	 	=> $this->options['sub_title'],
				'goods_list'	=> $this->getRecommendGoods(intval($this->options['img_recom_id']), 5, true, intval($this->options['img_cate_id'])),
			);
            $cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function getConfigDataSrc()
    {
		// 取得推荐类型
		$this->params['recommends'] = $this->getRecommendOptions(true);
		
        // 取得二级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, -1, null, 2);
    }

    public function parseConfig($input)
    {
        if ($input['img_recom_id'] >= 0)
        {
            $input['img_cate_id'] = 0;
        }

        return $input;
    }
}