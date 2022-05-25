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
use yii\helpers\ArrayHelper;

use common\models\CatePvsModel;

/**
 * @Id GoodsPropDistributeForm.php 2018.8.16 $
 * @author mosir
 */
class GoodsPropDistributeForm extends Model
{
	public $cate_id = 0;
	public $errors = null;

	public function formatData($result = null)
	{
		if(($catpv = CatePvsModel::find()->where(['cate_id' => $this->cate_id])->one())) {
			if(($pvs = explode(';', $catpv->pvs))) {
				$p = array();
				$v = array();
				foreach($pvs as $item) {
					$pv = explode(':', $item);
					$p[] = $pv[0];
					$v[] = $pv[1];
				}
				$p = array_unique($p);
				$v = array_unique($v);
			}
		}
			
		foreach($result as $key => $prop) {
			$result[$key]['checked'] = (isset($p) && in_array($prop['pid'], $p)) ? 1 : 0;
			foreach($prop['goodsPropValue'] as $_k => $_v) {
				$result[$key]['goodsPropValue'][$_k]['checked'] = (isset($v) && in_array($_v['vid'], $v)) ? 1 : 0;
			}
		}
		return $result;
	}
	
	public function valid($post)
	{
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = CatePvsModel::find()->where(['cate_id' => $this->cate_id])->one())) {
			$model = new CatePvsModel();
			$model->cate_id = $this->cate_id;
		}
		
		$pvs = '';
		foreach(ArrayHelper::toArray($post->vid) as $item) {
			$pv = explode(':', $item);
			if(in_array($pv[0], ArrayHelper::toArray($post->pid))) {
				$pvs .= ';' . $item;
			}
		}
		$model->pvs = substr($pvs, 1);
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
