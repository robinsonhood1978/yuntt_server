<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 
use yii\helpers\ArrayHelper;

use common\models\BankModel;
use common\models\DepositAccountModel;

use common\library\Language;

/**
 * @Id BankForm.php 2018.10.23 $
 * @author yxyc
 */
class BankForm extends Model
{
	public $bid = 0;
	public $errors = null;
	
	/** 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		$fields = ['num', 'account_name'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}

		// 验证支付密码
		if (!DepositAccountModel::checkAccountPassword($post->password, Yii::$app->user->id)) {
			$this->errors = Language::get(Language::get('password_error'));
			return false;
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !($this->valid($post))) {
			return false;
		}
		
		if($this->bid) {
			$model = BankModel::find()->where(['bid' => $this->bid, 'userid' => Yii::$app->user->id])->one();
		} 
		else {
			$model = new BankModel();
			$model->userid = Yii::$app->user->id;
		}
		
		if(isset($post->num)) $model->num = $post->num;
		if(isset($post->account_name)) $model->account_name = $post->account_name;
		if(isset($post->bank_name)) $model->bank_name = $post->bank_name;
		if(isset($post->short_name)) $model->short_name = $post->short_name;
		if(isset($post->type)) $model->type = $post->type;
		if(isset($post->open_bank)) $model->open_bank = $post->open_bank;

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		return ArrayHelper::toArray($model);
	}

	public function exists($post)
	{
		if(!BankModel::find()->where(['bid' => $this->bid, 'userid' => Yii::$app->user->id])->exists()) {
			$this->errors = Language::get('bank_invalid');
			return false;
		}
		return true;
	}
	
	/*
	 * 如果是新增，则一律判断
	 * 如果是编辑，则设置值了才判断
	 */
	public function isempty($post, $fields)
	{
		if($this->bid) {
			if(isset($post->$fields)) {
				return empty($post->$fields);
			}
			return false;
		}
		return empty($post->$fields);
	}
	
}
