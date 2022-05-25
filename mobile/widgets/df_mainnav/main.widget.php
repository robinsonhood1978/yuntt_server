<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace mobile\widgets\df_mainnav;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.13 $
 * @author mosir
 */

class Df_mainnavWidget extends BaseWidget
{
    var $name = 'df_mainnav';

    public function getData()
    {
        return $this->options;
    }

    public function parseConfig($input)
    {
        $result = array();

        $index = intval($input['index']);
        $num = isset($input['link']) ? count($input['link']) : 0;
        if ($num > 0)
        {
            for ($i = 0; $i < $num; $i++)
            {
				if ($index == $i && ($image = $this->upload("file[0]"))) {
            		$input['url'][$i] = $image;
				}
				
				if(!empty($input['url'][$i])) {
                    $result[] = array(
                        'url'   => $input['url'][$i],
                        'link'  => $input['link'][$i],
                        'title' => $input['title'][$i]
                    );
                }
            }
        }
        unset($input['url'], $input['link'], $input['file'], $input['title'], $input['index']);

        return array_merge(['images' => $result], $input);
    }   
}
