<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel1_floor2;

use Yii;

use common\models\BrandModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel1_floor2Widget extends BaseWidget
{
    var $name = 'df_channel1_floor2';

    public function getData()
    {
		return array(
			'model_id'  		=> mt_rand(),
			'model_name'		=> $this->options['model_name'],
			'keywords' 			=> explode(' ',$this->options['keywords']),
			'ad7_image_url' 	=> $this->options['ad7_image_url'],
			'ad7_title_url' 	=> $this->options['ad7_title_url'],
			'ad7_link_url' 		=> $this->options['ad7_link_url'],
			'ad8_image_url' 	=> $this->options['ad8_image_url'],
			'ad8_title_url' 	=> $this->options['ad8_title_url'],
			'ad8_link_url' 		=> $this->options['ad8_link_url'],
			'brands'      		=> array_chunk($this->getBrands($this->options['tag'], 15), 5),
			'ads'				=> $this->getAds(),
		);
    }

    public function parseConfig($input)
    {
		for ($i = 1; $i <= 8; $i++)
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
			$ads[$i]['ad_title_url'] = explode(' ',$this->options['ad'.$i.'_title_url']);
			$ads[$i]['ad_link_url'] = $this->options['ad'.$i.'_link_url'];
		}
		return $ads;
	}
	
	public function getBrands($tag, $num = 10)
	{
		$num = !empty($num) ? intval($num) : 10;
		
		$query = BrandModel::find()->where(['if_show' => 1, 'recommended' => 1]);
		if($tag) {
			$query->andWhere(['tag' => $tag]);
		}
		return $query->limit($num)->asArray()->all();
	}
}