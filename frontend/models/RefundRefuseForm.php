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
use common\models\UploadedFileModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Def;

/**
 * @Id RefundRefuseForm.php 2018.10.18 $
 * @author mosir
 */
class RefundRefuseForm extends Model
{
	public $errors = null;
	
	public function formData($post = null)
	{
		// 退款成功或退款关闭的退款，不能通行
		if(!$post->id || !($refund = RefundModel::find()->where(['refund_id' => $post->id, 'seller_id' => Yii::$app->user->id])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->asArray()->one())) {
			$this->errors = Language::get('refund_disallow');
			return false;
		}
		$refund['status_label'] = Language::get('REFUND_'.$refund['status']);
		
		return $refund;
	}
	
	public function valid($post = null)
	{
		// 退款成功或退款关闭的退款，不能通行
		if(!$post->id || !($refund = RefundModel::find()->where(['refund_id' => $post->id, 'seller_id' => Yii::$app->user->id])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->asArray()->one())) {
			$this->errors = Language::get('refund_disallow');
			return false;
		}
		return true;
	}
	
	/**
	 * 拒绝退款申请
	 * @api API接口用到该数据
	 */
	public function save($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		// 修改状态
		RefundModel::updateAll(['status' => 'SELLER_REFUSE_BUYER'], ['refund_id' => $post->id, 'seller_id' => Yii::$app->user->id]);

		$model = new RefundMessageModel();
		$model->owner_id = Yii::$app->user->id;
		$model->owner_role = 'seller';
		$model->refund_id = $post->id;
		$model->content = sprintf(Language::get('refuse_content_change'), $post->content);
		$model->created = Timezone::gmtime();
		
		if(($image = UploadedFileModel::getInstance()->upload('image', 0, Def::BELONG_REFUND_MESSAGE, $post->id))) {
			$model->image = $image;
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('add_fail');
			return false;
		}
		
		return true;
	}
}