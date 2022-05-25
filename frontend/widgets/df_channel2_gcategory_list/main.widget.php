<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_gcategory_list;

use Yii;

use common\models\GcategoryModel;
use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_gcategory_listWidget extends BaseWidget
{
    var $name = 'df_channel2_gcategory_list';

	public function getData()
    {
		$cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$data = array(
				'model_id'		=> mt_rand(),
				'model_name' 	=> $this->options['model_name'],
				'gcategories' 	=> $this->getGcategories(intval($this->options['cate_id'])),
				'keywords'    	=> trim($this->options['keyword']) ? explode(' ', trim($this->options['keyword'])) : '',
				'layer'		  	=> intval($this->options['layer']),
				'amount'	  	=> intval($this->options['amount']),
				'model_height'	=> floatval($this->options['model_height']),
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
	
	// 取三级（只用到三级）
	private function getGcategories($cate_id = 0)
	{ 
		$gcategories = GcategoryModel::getList($cate_id);
		foreach($gcategories as $key => $val) {
			$gcategories[$key]['children'] = GcategoryModel::getList($val['cate_id']);
			
			foreach($gcategories[$key]['children'] as $k => $v) {
				$gcategories[$key]['children'][$k]['children'] = GcategoryModel::getList($v['cate_id']);
			}
		}
		return $gcategories;
	} 
}