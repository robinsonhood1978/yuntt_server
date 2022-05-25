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

use common\models\OrderModel;
use common\library\Language;

/**
 * @Id Seller_orderMemoForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderMemoForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		if(!$post->order_id || !($orderInfo = OrderModel::find()->select('order_id,memo,flag')->where(['order_id' => $post->order_id, 'seller_id' => $this->store_id])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}
		return $orderInfo;
	}
	
	public function submit($post = null, $orderInfo = array())
	{
		if(($model = OrderModel::findOne($orderInfo['order_id']))) {
			$model->flag = $post->flag;
			$model->memo = $post->memo ? $post->memo : '';
			if(!$model->save()) {
				$this->errors = $model->errors;
				return false;
			}
			return true;
		} else {
			$this->errors = Language::get('no_such_order');
			return false;
		}
	}
}
