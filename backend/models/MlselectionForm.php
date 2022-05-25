<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model; 

use common\models\RegionModel;
use common\models\GcategoryModel;

/**
 * @Id MlselectionForm.php 2018.4.17 $
 * @author mosir
 * @desc Bank reset request form
 */
class MlselectionForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		if($post->type == 'region') 
		{
			$regions = RegionModel::getList($post->pid);
			foreach($regions as $key => $region)
			{
    			$regions[$key]['mls_name'] = htmlspecialchars($region['region_name']);
				$regions[$key]['mls_id'] = htmlspecialchars($region['region_id']);
   			}
			return array_values($regions);
		}
     	elseif($post->type == 'gcategory')
		{
			$gcategorys = GcategoryModel::getList($post->pid, $post->store_id, true);
    		foreach ($gcategorys as $key => $gcategory)
   			{
 				$gcategorys[$key]['mls_name'] = htmlspecialchars($gcategory['cate_name']);
				$gcategorys[$key]['mls_id'] = htmlspecialchars($gcategory['cate_id']);
   			}
			return array_values($gcategorys);
        }
		return array();
	}
}
