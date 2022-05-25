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

use common\models\AddressModel;

use common\library\Language;

/**
 * @Id AddressDeleteForm.php 2018.4.23 $
 * @author mosir
 */
class AddressDeleteForm extends Model
{
	public $addr_id = 0;
	public $errors = null;

	public function valid($post)
	{
		if (!$this->addr_id) {
			$this->errors = Language::get('no_such_address');
			return false;
        }
		return true;
	}
	
	public function delete($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(AddressModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['in', 'addr_id', explode(',', $this->addr_id)]])) {
			AddressModel::setIndexAddress();
			return true;	
		}
		return false;
	}
}
