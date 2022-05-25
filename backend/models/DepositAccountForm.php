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

use common\models\DepositAccountModel;

use common\library\Language;
use common\library\Basewind;

/**
 * @Id DepositAccountForm.php 2018.8.19 $
 * @author mosir
 */
class DepositAccountForm extends Model
{
	public $account_id = 0;
	public $errors = null;
	
    public function valid($post)
	{
		if(!Basewind::isPhone($post->account) && !Basewind::isEmail($post->account)) {
			$this->errors = Language::get('account_invalid');
			return false;
		}
		// edit
		if($this->account_id) {
			if(DepositAccountModel::find()->where(['and', ['account' => $post->account], ['!=', 'account_id', $this->account_id]])->exists()) {
				$this->errors = Language::get('account_exist');
				return false;
			}
		}
		
		if(empty($post->real_name)) {
			$this->errors = Language::get('real_name_not_empty');
			return false;
		}
		if(isset($post->password) && $post->password && (strlen($post->password) < 6 || strlen($post->password) > 40)) {
			$this->errors = Language::get('password_limit');
			return false;
		}
		
		return true;
	}
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!($model = DepositAccountModel::find()->where(['account_id' => $this->account_id])->one())) {
			$model = new DepositAccountModel();
		}
		$model->account = $post->account;
		$model->real_name = $post->real_name;
		$model->pay_status = in_array(strtoupper($post->pay_status), ['ON', 'OFF']) ? $post->pay_status : 'OFF';
		if(isset($post->password) && $post->password) $model->password = md5($post->password);
		return $model->save() ? true : false;
	}
}
