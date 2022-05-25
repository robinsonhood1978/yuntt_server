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

use common\models\StoreModel;
use common\models\CategoryStoreModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id StoreForm.php 2018.8.16 $
 * @author mosir
 */
class StoreForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		// edit
		if($this->store_id) {
			if(StoreModel::find()->where(['store_name' => $post->store_name])->andWhere(['!=', 'store_id', $this->store_id])->exists()) {
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
		
		// 留存旧数据，后续用到
		$store = StoreModel::find()->select('state')->where(['store_id' => $this->store_id])->one();
		
		if(!($model = StoreModel::findOne($this->store_id))) {
			$model = new StoreModel();
		}
		$fields = ['store_name', 'owner_name', 'identity_card', 'region_id', 'region_name', 'address', 'zipcode', 'tel', 'sgrade', 'sort_order', 'recommended'];
		foreach($post as $key => $val) {
			if(in_array($key, $fields)) $model->$key = $val;
		}
		
		$model->end_time = Timezone::gmstr2time($post->end_time);
		if(in_array($post->state, [Def::STORE_OPEN, Def::STORE_CLOSED])) { // 其他状态值不要插值
			$model->state = $post->state;
			$model->apply_remark = '';
		}
		if($post->state == Def::STORE_CLOSED) {
			$model->close_reason = isset($post->close_reason) ? $post->close_reason : '';
		}
		$certs = array();
		if(isset($post->autonym) && $post->autonym) $certs[] = 'autonym'; 
		if(isset($post->material) && $post->material) $certs[] = 'material';
		$model->certification = $certs ? implode(',', $certs) : '';
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		if(isset($post->cate_id) && $post->cate_id) {
			if(!($query = CategoryStoreModel::find()->where(['store_id' => $this->store_id])->one())) {
				$query = new CategoryStoreModel();
			}
			$query->cate_id = $post->cate_id;
			$query->save();
		}

		// 如果修改了店铺状态，通知店主
		if($store && ($store->state != $post->state) && in_array($post->state, [Def::STORE_OPEN, Def::STORE_CLOSED])) {
			
			$retval = ['store_name' => $model->store_name, 'owner_name' => $model->owner_name];
			$reason = isset($post->close_reason) ? $post->close_reason : '';
			 
			if($post->state == Def::STORE_CLOSED) $notify = 'toseller_store_closed_notify';
			if($post->state == Def::STORE_OPEN) $notify = 'toseller_store_open_notify';
	
			 // 发站内信
			$pmer = Basewind::getPmer($notify, ['store' => $retval, 'reason' => $reason]);
			if($pmer) {
				$pmer->sendFrom(0)->sendTo($model->store_id)->send();
			}
			// 发邮件
			$mailer = Basewind::getMailer($notify, ['store' => $retval, 'reason' => $reason]);
			if($mailer && ($toEmail = UserModel::find()->select('email')->where(['userid' => $this->store_id])->scalar())) {
				$mailer->setTo($toEmail)->send();
			}
			
			// 清空缓存
			Yii::$app->cache->flush();
		}
		return true;
	}
	
	public function batchFormData($post)
	{
		$query = StoreModel::find()->where(['store_id' => $this->store_id])->asArray()->one();
		if($post->cate_id <= 0) unset($post->cate_id);
		if($post->region_id <= 0) {
			unset($post->region_id);
			unset($post->region_name);
		}
		if($post->sgrade <= 0) unset($post->sgrade);
		if($post->recommended < 0) unset($post->recommended);
		if($post->sort_order <= 0) unset($post->sort_order);
		if($post->certification > 0) {
			$certs = array();
			if($post->autonym) $certs[] = 'autonym';
			if($post->material) $certs[] = 'material';
			$post->certification = $certs ? implode(',', $certs) : '';
		}
		return Basewind::trimAll(array_merge($query, ArrayHelper::toArray($post)), true);
	}
}