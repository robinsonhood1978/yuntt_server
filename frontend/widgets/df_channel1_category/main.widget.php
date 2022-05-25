<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel1_category;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\BrandModel;
use common\models\GcategoryModel;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel1_categoryWidget extends BaseWidget
{
    var $name = 'df_channel1_category';

  	public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$data = array(
				'model_name'  => $this->options['model_name'],
				'gcategories' => $this->getGcategories(),
			);
            $cache->set($key, $data, $this->ttl);
        }
		return $data;
    }

	public function getConfigDataSrc()
    {
        // 取得一级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, 0);
    }
	public function getGcategories()
	{
     	$gcategories = array();
    	if(!empty($this->options['cate_id']))
      	{
      		$gcategories = GcategoryModel::getList(intval($this->options['cate_id']));
			foreach($gcategories as $key => $cate)
			{
				$gcategories[$key]['child'] = GcategoryModel::getList($cate['cate_id']);
				$gcategories[$key]['brand'] = $this->getBrands($cate['cate_name'],15);
			}
      	}
		return $gcategories;
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