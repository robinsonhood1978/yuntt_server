<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_store;

use Yii;

use common\models\StoreModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_storeWidget extends BaseWidget
{
    var $name = 'df_store';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {			
			$data = array(
				'model_name' => $this->options['model_name'],
				'storeList'	 => $this->getStoreList(),
			);	
			
			$cache->set($key, $data, $this->ttl);
		}
        return $data;
	}
	
	public function getStoreList($num = 4)
	{
		$storeList = [];
		for ($i = 1; $i <= 4; $i++)
        {
			$storeList[$i]['title']	= $this->options['ad'.$i.'_title'];
			$storeList[$i]['image']		= $this->options['ad'.$i.'_image_url'];
			if($this->options['ad'.$i.'_storeId']){
				$store = StoreModel::find()->select('store_logo,store_name,store_id')->where(['store_id' => $this->options['ad'.$i.'_storeId']])->one();
				if(!empty($store)){
					$storeList[$i]['store_id'] = $store['store_id'];
					$storeList[$i]['store_name'] = $store['store_name'];
					$storeList[$i]['store_logo'] = empty($store['store_logo']) ? Yii::$app->params['default_store_logo'] : $store['store_logo'];
				}
			}
			
		}
		return $storeList;
	}
	
    public function parseConfig($input)
    {
		for ($i = 1; $i <= 4; $i++)
        {
			if($image = $this->upload('ad'.$i.'_image_file')) {
				$input['ad' . $i . '_image_url'] = $image;
			}
        }	
        return $input;
    }
}
