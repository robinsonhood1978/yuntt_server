<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_floor1;

use Yii;

use common\models\GcategoryModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_floor1Widget extends BaseWidget
{
    var $name = 'df_channel2_floor1';

    public function getData()
    {
		$cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
            $images = array();
			for ($i = 1;$i < 5; $i++) {
				$images['ad'.$i.'_image_url'] = $this->options['ad'.$i.'_image_url'];
				$images['ad'.$i.'_link_url']  = $this->options['ad'.$i.'_link_url'];
			}

			$data = array(
				'model_id'	=> mt_rand(),
				'model_name'=> $this->options['model_name'],
				'goods_list'=> $this->getRecommendGoods(intval($this->options['img_recom_id_1']), 5, true, intval($this->options['img_cate_id_1'])),
				'rank'      => $this->getRecommendGoods(intval($this->options['img_recom_id_2']), 5, true, intval($this->options['img_cate_id'])),
				'cates'     => $this->getCats(),
				'images'    => $images,
				'keywords'  => explode('|',$this->options['keyword']),
			);
        	$cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function parseConfig($input)
    {
		for ($i = 1;$i < 5; $i++) 
		{
			if (($image = $this->upload('ad'.$i.'_image_file'))) {
            	$input['ad' . $i . '_image_url'] = $image;
       		}
        }
        return $input;
    }

	public function getConfigDataSrc()
    {
		// 取得推荐类型
		$this->params['recommends'] = $this->getRecommendOptions(true);
		
        // 取得二级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, -1, null, 2);
    }
	
	public function getCats()
	{
		$result = array();
		if(!empty($this->options['cate_ids']))
		{
			$allId = explode(',', $this->options['cate_ids']);
			foreach($allId as $key => $val) {
				$allId[$key] = intval($val);
			}
			$result = GcategoryModel::find()->select('cate_id,cate_name')->indexBy('cate_id')->where(['in', 'cate_id',  $allId])->andWhere(['store_id' => 0])->asArray()->all();
		}
		return $result;
	}
}