<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_fl_brand;

use Yii;

use common\models\BrandModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_fl_brandWidget extends BaseWidget
{
    var $name = 'df_fl_brand';
	
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
            $amount = intval($this->options['amount']);
            if($amount > 0) {
                $query->limit($amount);
            }
            $data = array(
                'model_name' => $this->options['model_name'],
				'brandList'	=> $query->orderBy(['sort_order' => SORT_ASC])->asArray()->all()
			);
            $cache->set($key, $data, $this->ttl);
        }
		
        return $data;	
    }	
}
