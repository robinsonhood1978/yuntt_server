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

use common\models\RefundModel;
use common\models\RefundMessageModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id RefundInterveneForm.php 2018.10.17 $
 * @author mosir
 */
class RefundInterveneForm extends Model
{
	public $errors = null;
	
	public function valid($post = null)
	{
		if(!$post->id || !($refund = RefundModel::find()->select('refund_id,buyer_id')->where(['intervene' => 0, 'refund_id' => $post->id])->andWhere(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->one())) {
			$this->errors = Language::get('intervene_disallow');
			return false;
		}
		return true;
	}
	
	/**
	 * 同时兼容API接口
	 */
	public function save($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		$refund = RefundModel::findOne($post->id);

		// 后台管理员干预退款
		$refund->intervene = 1;
		if(!$refund->save()) {
			$this->errors = Language::get('intervene_apply_fail');
			return false;
		}
	
		// 同时插入退款处理日志
		$model = new RefundMessageModel();
		$model->owner_id = Yii::$app->user->id;
		$model->owner_role = $refund->buyer_id == Yii::$app->user->id ? 'buyer' : 'seller';
		$model->refund_id = $post->id;
		$model->content = sprintf(Language::get('intervene_content_change'), Language::get($model->owner_role));
		$model->created = Timezone::gmtime();
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('refund_message_save_fail');
			return false;
		}
		return true;		
	}
}
