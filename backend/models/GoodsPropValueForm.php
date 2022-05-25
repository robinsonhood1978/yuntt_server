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

use common\models\GoodsPropValueModel;

use common\library\Language;

/**
 * @Id GoodsPropValueForm.php 2018.8.15 $
 * @author mosir
 */
class GoodsPropValueForm extends Model
{
	public $pid = 0;
	public $vid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		// edit
		if($this->vid) {
			$this->pid = $this->pid ? $this->pid : GoodsPropValueModel::find()->select('pid')->where(['vid' => $this->vid])->scalar();
			if(GoodsPropValueModel::find()->where(['pid' => $this->pid, 'pvalue' => $post->pvalue])->andWhere(['!=', 'vid', $this->vid])->exists()) {
				$this->errors = Language::get('value_exist');
				return false;
			}
		}
		// add
		else
		{
			if(!$this->pid) {
				$this->errors = Language::get('no_such_prop');
				return false;
			}
			if(GoodsPropValueModel::find()->where(['pid' => $this->pid, 'pvalue' => $post->pvalue])->exists()) {
				$this->errors = Language::get('value_exist');
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
		
		if(!$this->vid || !($model = GoodsPropValueModel::findOne($this->vid))) {
			$model = new GoodsPropValueModel();
			$model->pid = $this->pid;
		}
		$model->pvalue = $post->pvalue;
		$model->color = $post->color;
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		$model->status = $post->status;
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		return true;
	}
}
