<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel1_three_image_ads;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel1_three_image_adsWidget extends BaseWidget
{
    var $name = 'df_channel1_three_image_ads';

    public function getData()
    {
        return array(
			'model_id'		=> mt_rand(),
			'model_name'	=> $this->options['model_name'],
            'ad1_image_url' => $this->options['ad1_image_url'],
            'ad1_link_url'  => $this->options['ad1_link_url'],
            'ad2_image_url' => $this->options['ad2_image_url'],
            'ad2_link_url'  => $this->options['ad2_link_url'],
            'ad3_image_url' => $this->options['ad3_image_url'],
            'ad3_link_url'  => $this->options['ad3_link_url'],
        );
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 3; $i++) 
		{
			if (($image = $this->upload('ad'.$i.'_image_file'))) {
            	$input['ad' . $i . '_image_url'] = $image;
       		}
        }
        return $input;
    } 
}