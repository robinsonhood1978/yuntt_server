<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_master;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_masterWidget extends BaseWidget
{
    var $name = 'df_master';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {		
			$data = array(
				'model_name' => $this->options['model_name'],
				'list'		 => $this->getList(),	
			);
            $cache->set($key, $data, $this->ttl);
		}
        return $data;	
    }
	
	public function getList()
	{
		$list = array();
		for($i = 1; $i <= 5; $i++)
		{
			$list[] = array(
				'title'	=> explode('|', $this->options['model'.$i.'_title']),
				'url'   => $this->options['ad'.$i.'_image_url'],
            	'link'  => $this->options['ad'.$i.'_link_url'],
				'style'	=> $this->options['model_style'.$i],
			);
		}	
		return $list;
	}
	
    public function parseConfig($input)
    {
		for ($i = 1; $i <= 5; $i++)
        {
			if(($image = $this->upload('ad'.$i.'_image_file'))) {
				$input['ad' . $i . '_image_url'] = $image;
			}
        }	
        return $input;
    }
	
}
