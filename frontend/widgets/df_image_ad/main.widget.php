<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_image_ad;

use Yii;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */

class Df_image_adWidget extends BaseWidget
{
    var $name = 'df_image_ad';

    public function getData()
    {
        return array(
            'ad_image_url'  	=> $this->options['ad_image_url'],
            'ad_link_url'   	=> $this->options['ad_link_url'],
			'width'      		=> $this->options['width'],
			'height'     		=> $this->options['height'],
			'border'     		=> $this->options['border'],
			'margin'     		=> $this->options['margin'],
			'background'  	    => $this->options['background'],
			'closeButton'		=> $this->options['closeButton']
        );
    }

    public function parseConfig($input)
    {
        if (($image = $this->upload('ad_image_file'))) {
            $input['ad_image_url'] = $image;
        }
        return $input;
    }
}
