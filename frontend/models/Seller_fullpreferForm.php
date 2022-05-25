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
use yii\helpers\ArrayHelper;

use common\models\PromotoolSettingModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id Seller_fullfreeForm.php 2018.11.15 $
 * @author luckey
 */
class Seller_fullpreferForm extends Model
{
	public $store_id = 0;
	public $appid = 'fullprefer';
	public $errors = null;

	public function valid($post)
	{
		if($post->amount <= 0) {
			$this->errors = Language::get('not_allempty');
			return false;
		}

		if((!$post->discount && !$post->decrease) || ($post->discount && $post->decrease)) {
			$this->errors = Language::get('pls_select_type');
			return false;
		}

		if($post->discount) {
			if($post->discount <= 0 || $post->discount >= 10) {
				$this->errors = Language::get('discount_invalid');
				return false;
			}
		}
		elseif($post->decrease) {
			if($post->decrease <= 0) {
				$this->errors = Language::get('price_le_0');
				return false;
			}
			elseif($post->amount <= $post->decrease) {
				$this->errors = Language::get('amount_le_decrease');
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
		
		if(($message = Promotool::getInstance($this->appid)->build(['store_id' => $this->store_id])->checkAvailable(true, false)) !== true) {
			$this->errors = $message;
			return false;
		}

		if(!($model = PromotoolSettingModel::find()->where(['appid' => $this->appid, 'store_id' => $this->store_id])->orderBy(['psid' => SORT_DESC])->one())) {
			$model = new PromotoolSettingModel();
			$model->add_time = Timezone::gmtime();
		}

		if($post->discount) {
			$post->discount = round(floatval($post->discount), 1); 
			$post->type = 'discount';
			unset($post->decrease);
		}elseif($post->decrease) {
			$post->decrease = abs(floatval($post->decrease));
			$post->type = 'decrease';
			unset($post->discount);
		}
		$model->appid = $this->appid;
		$model->store_id = $this->store_id;
		$model->status = intval($post->status);

		unset($post->status);
		$model->rules = serialize(ArrayHelper::toArray($post));
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		PromotoolSettingModel::deleteAll(['and', ['appid' => $this->appid], ['store_id' => $this->store_id], ['!=', 'psid', $model->psid]]);
		return true;
	}
}
