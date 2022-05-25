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
 * @Id GoodsPropDeleteForm.php 2018.8.15 $
 * @author mosir
 */
class GoodsPropDeleteForm extends Model
{
	public $pid = 0;
	public $errors = null;
	
	public function valid($post)
	{
		$pid = explode(',', $this->pid);
		if(!$this->pid || !is_array($pid) || empty($pid)) {
			$this->errors = Language::get('no_such_prop');
			return false;
		}
		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		GoodsPropModel::deleteAll(['in', 'pid', explode(',', $this->pid)]);
		GoodsPropValueModel::deleteAll(['in', 'pid', explode(',', $this->pid)]);
		
		return true;
	}
}
