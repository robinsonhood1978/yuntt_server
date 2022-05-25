<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\RegionModel;
use common\models\DeliveryTemplateModel;

use common\library\Basewind;

/**
 * @Id LogisticForm.php 2018.4.17 $
 * @author mosir
 */
class LogisticForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		if(isset($post->template_id) && $post->template_id) {
			$delivery = DeliveryTemplateModel::find()->where(['template_id' => $post->template_id])->asArray()->one();
		}
		
		// 如果商品没有设置运费模板，取店铺默认的运费模板
		if(empty($delivery)) {
			if(isset($post->store_id) && $post->store_id) {
				$delivery = DeliveryTemplateModel::find()->where(['store_id' => $post->store_id])->orderBy(['template_id' => SORT_ASC])->asArray()->one();
				
				// 如果还是没有，添加一条默认的
				if(empty($delivery)) {
					$delivery = DeliveryTemplateModel::addFirstTemplate($post->store_id);
				}
			}  else return false;
		}
		
		// 如果不传城市ID，则通过IP自动识别所在城市
		if(!isset($post->city_id) || !$post->city_id) {
			$post->city_id = RegionModel::getCityIdByIp();
		}
		
		$result = [
			'logistic_fee' => DeliveryTemplateModel::getCityLogistic($delivery, $post->city_id),
			//'city_name'  => RegionModel::getRegionName($post->city_id),
		];
		
		if(Basewind::getCurrentApp() == 'pc') {
			$result['city_name'] = RegionModel::getRegionName($post->city_id);
		}
		
		return $result;
	}
}
