<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_three_group;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_three_groupWidget extends BaseWidget
{
    var $name = 'df_channel2_three_group';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$data = array(
				'model_id'  	=> mt_rand(),
				'model_name'	=> $this->options['model_name'],
				'tabList'	  	=> $this->getTab(),
			);
			$cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 3; $i++)
        {
			for($j = 1; $j <= 5; $j++)
			{
				if(($image = $this->upload('ad'.$i.'_image_file_'.$j))) {
					$input['ad' . $i . '_image_url_'.$j] = $image;
				}
			}
		}
        return $input;
    }
	
	public function getTab()
	{
		$list = array();
		for($i = 1; $i <= 3; $i++)
		{
			$tab = array();
			if(!empty($this->options['cate_name_'.$i])) {
				$tab['cate_name'] = $this->options['cate_name_'.$i];
				for($j = 1; $j <= 5; $j++) {
					$tab['images'][] = array('url' => $this->options['ad'.$i.'_image_url_'.$j], 'link' => $this->options['ad'.$i.'_link_url_'.$j]);
				}
			}	
			$list[] = $tab;
		}
		return $list;
	}
}
