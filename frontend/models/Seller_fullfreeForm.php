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
 * @Id Seller_fullfreeForm.php 2018.11.13 $
 * @author luckey
 */
class Seller_fullfreeForm extends Model
{
	public $store_id = 0;
	public $appid = 'fullfree';
	public $errors = null;

	public function valid(&$post)
	{
		if(isset($post->quantity)) {
			$post->quantity = intval($post->quantity);
		}
		if($post->amount) {
			$post->amount = abs(floatval($post->amount));
		}

		if(!$post->amount && $post->quantity <= 0) {
			$this->errors = Language::get('not_allempty');
			return false;
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
		
		$model->appid = $this->appid;
		$model->store_id = $this->store_id;
		$model->status = $post->status;

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
