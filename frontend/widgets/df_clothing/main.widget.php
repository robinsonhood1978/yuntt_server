<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_clothing;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_clothingWidget extends BaseWidget
{
    var $name = 'df_clothing';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {	
			$data = array(
				'model_id' 			=> mt_rand(),
				'model_name'		=> $this->options['model_name'],
				'model_style'       => $this->options['model_style'],
				
				'ad4_image_url'		=> $this->options['ad4_image_url'],
				'ad4_link_url'		=> $this->options['ad4_link_url'],
				'ad5_image_url'		=> $this->options['ad5_image_url'],
				'ad5_link_url'		=> $this->options['ad5_link_url'],
				
				'goods_list'		=> $this->getGoodsList(),
				'left_side'			=> $this->getLeftSide(),	
				'right_side' 		=> $this->getRightSide()
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
	
	public function getLeftSide()
	{
		// 左侧的滚动商品
		$list = array();
		for($i = 1; $i <= 3; $i++)
		{
			$list[] = array(
				'url'   => $this->options['ad'.$i.'_image_url'],
            	'link'  => $this->options['ad'.$i.'_link_url']
			);
		}	
		return $list;
	}
	
	public function getRightSide()
	{
		// 右侧的6张商品图
		$list = array();
		for($i = 6; $i <= 11; $i++)
		{
			$list[] = array(
				'title'	=> explode('|', $this->options['model'.$i.'_title']),
				'url'	=> $this->options['ad'.$i.'_image_url'],
				'link'  => $this->options['ad'.$i.'_link_url']
			);
		}
		return $list;
	}
	
	public function getGoodsList($num = 6)
	{
		$goods_list = array();
		for($i = 1; $i <= 5; $i++)  
		{	
			$list = array();
			if(trim($this->options['tab'.$i.'_title']))
			{
				$title = $this->options['tab'.$i.'_title'];
				if($i > 1) {
					$list = $this->getRecommendGoods(intval($this->options['img_recom_id_'.$i]), $num, true, intval($this->options['img_cate_id_'.$i]));
					$cut = 0;
				}
				else {
					$cut = 1;
				}	
				$goods_list[] = array(
					'title' => $title,
					'list'  => $list,
					'cut'   => $cut
				);
			}
		}	
		return $goods_list;	
	}
	
    public function parseConfig($input)
    {
		for ($i = 1; $i <= 11; $i++)
        {
			if(($image = $this->upload('ad'.$i.'_image_file'))) {
				$input['ad' . $i . '_image_url'] = $image;
			}
        }	
        return $input;
    }
}
