<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\widgets\df_rubikcube;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_rubikcubeWidget extends BaseWidget
{
    var $name = 'df_rubikcube';

    public function getData()
    {
        if(!empty($this->options['ad_image_url'])) {
            foreach($this->options['ad_image_url'] as $key => $value) {
                if($value) {
                    $this->options['images'][] = ['url' => $value, 'link' => $this->options['ad_link_url'][$key]];
                }
            }
            unset($this->options['ad_image_url'], $this->options['ad_link_url']);
        }

        return $this->options;
    }

    public function parseConfig($input)
    {
        $result = array();

        $index = intval($input['index']);
        unset($input['index'], $input['ad_image_file']);

        $num = isset($input['ad_link_url']) ? count($input['ad_link_url']) : 0;
        if ($num > 0)
        {
            for ($i = 0; $i < $num; $i++)
            {
				if ($index == $i && ($image = $this->upload("ad_image_file[0]"))) {
            		$input['ad_image_url'][$i] = $image;
				}
            }
        }
        
        return $input;
    }   
}
