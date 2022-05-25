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

use common\models\DeliveryTemplateModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id My_deliveryForm.php 2018.10.2 $
 * @author luckey
 */
class My_deliveryForm extends Model
{
	public $id = 0;
	public $store_id = null;
	public $errors = null;
	
	public function valid(&$post)
	{
		if(empty($post['name'])) {
			$this->errors = Language::get('name_empty');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$dests = $start = $postage = $plus = $postageplus = '';
		foreach($post['types'] as $type)
		{
			// 检查是否有未设置地区项
			foreach($post[$type.'_dests'] as $item) {
				if(trim($item) === '') {
					$this->errors = Language::get('set_region_pls');
					return false;
				}
			}

			$dests .=';'.implode(',',$post[$type.'_dests']);
			$start .=';'.implode(',',$post[$type.'_start']);
			$postage .=';'.implode(',', $post[$type.'_postage']);
			$plus .=';'.implode(',', $post[$type.'_plus']);
			$postageplus .=';'.implode(',', $post[$type.'_postageplus']);
		}
		
		if(!$this->id || !($model = DeliveryTemplateModel::find()->where(['template_id' => $this->id, 'store_id' => $this->store_id])->one())) {
			$model = new DeliveryTemplateModel();
			$model->created = Timezone::gmtime();
		}

		$model->name = $post['name'];
		$model->store_id = $this->store_id;
		$model->types = implode(';',$post['types']);
		$model->dests = substr($dests,1);
		$model->start_standards = substr($start,1);
		$model->start_fees = substr($postage,1);
		$model->add_standards = substr($plus,1);
		$model->add_fees = substr($postageplus,1);
		
		if(!$this->checkVal($model->start_standards) || !$this->checkVal($model->start_fees) || !$this->checkVal($model->add_standards) || !$this->checkVal($model->add_fees)) {
			$this->errors = Language::get('fee_and_quantity_must_number');
			return false;
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
        return true;
	}
	
	private function checkVal($string = '')
	{
		foreach(explode(';', $string) as $key => $val)
		{
			foreach(explode(',', $val) as $k => $v)
			{
				if(!is_numeric($v) || $v < 0 || $v == ''){
					return false;
				}
			}
		}
		return true;
	}
}
