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

use common\models\GoodsPropModel;
use common\models\GoodsPropValueModel;

use common\library\Language;

/**
 * @Id GoodsPropForm.php 2018.8.15 $
 * @author mosir
 */
class GoodsPropForm extends Model
{
	public $pid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		// edit
		if($this->pid) {
			if(GoodsPropModel::find()->where(['name' => $post->name])->andWhere(['!=', 'pid', $this->pid])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		// add
		else
		{
			if(GoodsPropModel::find()->where(['name' => $post->name])->exists()) {
				$this->errors = Language::get('name_exist');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!$this->pid || !($model = GoodsPropModel::findOne($this->pid))) {
			$model = new GoodsPropModel();
		}
		$model->name = $post->name;
		$model->ptype = $post->ptype ? $post->ptype : 'select';
		$model->is_color = $post->is_color;
		$model->sort_order = $post->sort_order;
		$model->status = $post->status;
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		if($post->pvalue) {
			foreach(explode(',', $post->pvalue) as $val) {
				if(!GoodsPropValueModel::find()->where(['pid' => $model->pid, 'pvalue' => $val])->exists()) {
					$query = new GoodsPropValueModel();
					$query->pid = $model->pid;
					$query->pvalue = $val;
					$query->status = 1;
					$query->sort_order = 255;
					$query->save();
				}
			}
		}
		return true;
	}
}
