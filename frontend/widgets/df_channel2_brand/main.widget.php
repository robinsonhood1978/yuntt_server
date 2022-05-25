<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_brand;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_brandWidget extends BaseWidget
{
    var $name = 'df_channel2_brand';

    public function getData()
    {
		$cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$data = array(
				'model_id' 		 => mt_rand(),
				'ads'  			 => $this->getAds(),
				'model_name'	 => $this->options['model_name'],
				'ad_m_title_url' => $this->options['ad_m_title_url'],
				'ad_s_title_url' => $this->options['ad_s_title_url'],
			);
        	$cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 7; $i++) 
		{
			if (($image = $this->upload('ad'.$i.'_image_file'))) {
            	$input['ad' . $i . '_image_url'] = $image;
       		}
        }
        return $input;
    }
	public function getAds()
	{
		$ads = array();
		for($i = 1; $i < 7; $i++)
		{
			$ads[$i]['ad_image_url'] = $this->options['ad'.$i.'_image_url'];
			$ads[$i]['ad_link_url'] = $this->options['ad'.$i.'_link_url'];
			!empty($this->options['ad'.$i.'_title_url']) && $title[$i] = explode(',', $this->options['ad'.$i.'_title_url']);
			$ads[$i]['ad_m_title_url'] = $title[$i][0];
			$ads[$i]['ad_s_title_url'] = $title[$i][1];
		}
		return $ads;
	}
}