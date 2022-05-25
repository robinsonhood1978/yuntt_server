<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_bag_brand;

use Yii;

use common\models\BrandModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_bag_brandWidget extends BaseWidget
{
    var $name = 'df_bag_brand';
	
    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
            $query = BrandModel::find()->select('brand_logo, brand_id');
            if(($btag = $this->options['btag'])) {
                $query->where(['tag' => $btag]);
            }
            $data = array(
				'brandlist'		=> $query->limit(18)->asArray()->all(),
				'ad_image_url'	=> $this->options['ad_image_url'],
				'ad_link_url'	=> $this->options['ad_link_url'],
			);
            $cache->set($key, $data, $this->ttl);
        }
		
        return $data;	
    }
	
    public function parseConfig($input)
    {
        if (($image = $this->upload('ad_image_file'))) {
            $input['ad_image_url'] = $image;
        }
        return $input;
    }
}
