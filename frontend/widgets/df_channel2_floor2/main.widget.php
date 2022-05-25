<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_floor2;

use Yii;

use common\models\GcategoryModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_floor2Widget extends BaseWidget
{
    var $name = 'df_channel2_floor2';

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
				'keywords'  	=> explode('|',$this->options['keyword']),
				'cates'         => $this->getCats(), // 左侧分类
				'ad1_image_url' => $this->options['ad1_image_url'],
				'ad1_link_url'  => $this->options['ad1_link_url'],
				'cate_name'		=> $this->getMiddleTab(), // 中部切换标题
				'slide_images'  => $this->getMiddleSlides(), // 中部幻灯片
                'floor_images'  => $this->getMiddleFloorImage(), // 中部其他5张图片
				'floor_goods'	=> $this->getMiddleFloorGoods(), // 中部其他3个切换（读商品）
				'tab_goods'		=> $this->getRightTabGoods(), // 右侧2个切换（读商品）
			);
        	$cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 9; $i++) 
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
	
	public function getMiddleTab()
	{
		$cate_name = array();
		for($i = 1; $i <= 4; $i++){
			$cate_name[] = $this->options['cate_name_'.$i];
		}
		return $cate_name;
	}
	public function getMiddleSlides()
	{
		$slide_images = array();
		for($i = 2; $i<= 4; $i++) {
			$slide_images[] = array('url' => $this->options['ad'.$i.'_image_url'], 'link' => $this->options['ad'.$i.'_link_url']);
		}
		return $slide_images;
	}
	public function getMiddleFloorImage()
	{
		$floor_images = array();
		for($i = 5; $i <= 9; $i++) {
			$floor_images[] = array('url' => $this->options['ad'.$i.'_image_url'], 'link' => $this->options['ad'.$i.'_link_url']);
		}
		return $floor_images;
	}
	public function getMiddleFloorGoods()
	{
		$floor_goods = array();
		for($i = 2; $i <= 4; $i++){
			$floor_goods_item = array();
			if($this->options['cate_name_'.$i]) {
				$floor_goods_item['cate_name'] = $this->options['cate_name_'.$i];
				$floor_goods_item['goods_list'] = $this->getRecommendGoods(intval($this->options['img_recom_id_'.$i]), 8, true, intval($this->options['img_cate_id_'.$i]));
			}	
			$floor_goods[] = $floor_goods_item;
		}
		return $floor_goods;
	}
	public function getRightTabGoods()
	{
		$tab_goods = array();
		for($i = 5; $i <= 6; $i++){
			$tab_goods_item = array();
			if($this->options['cate_name_'.$i]) {
				$tab_goods_item['cate_name'] = $this->options['cate_name_'.$i];
				$tab_goods_item['goods_list'] = $this->getRecommendGoods(intval($this->options['img_recom_id_'.$i]), 5, true, intval($this->options['img_cate_id_'.$i]));
			}	
			$tab_goods[] = $tab_goods_item;
		}
		return $tab_goods;
	}
}
