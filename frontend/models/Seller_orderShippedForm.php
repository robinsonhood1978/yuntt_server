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
use common\models\DepositTradeModel;
use common\models\OrderLogModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id Seller_orderShippedForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderShippedForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		if(!$post->order_id || !($orderInfo = OrderModel::find()->alias('o')->select('o.order_id,order_sn,buyer_id,seller_id,buyer_name,seller_name,express_no,express_comkey,ex.phone_mob')->joinWith('orderExtm ex', false)->where(['o.order_id' => $post->order_id, 'seller_id' => $this->store_id])->andWhere(['in', 'status', [Def::ORDER_SUBMITTED, Def::ORDER_ACCEPTED, Def::ORDER_SHIPPED]])->asArray()->one())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}
		return $orderInfo;
	}
	
	/**
	 * 卖家发货
	 * @desc API接口用到此
	 */
	public function submit($post = null, $orderInfo = array(), $sendNotify = true)
	{
		if (!$post->express_no) {
			$this->errors = Language::get('express_no_empty');
   			return false;
    	}
			
		$model = OrderModel::findOne($orderInfo['order_id']);
		$model->status = Def::ORDER_SHIPPED;
			
		if(!$model->express_no) {
			$model->ship_time = Timezone::gmtime();
		}
		$model->express_no = $post->express_no;
		
		// 取得一个可用的快递跟踪插件
		if(($expresser = Plugin::getInstance('express')->autoBuild())) {
			if(!$post->express_comkey) {
				$this->errors = Language::get('express_company_empty');
				return false;
			}
			$model->express_code = $expresser->getCode();
			$model->express_comkey = $post->express_comkey;
			$model->express_company = $expresser->getCompanyName($post->express_comkey);
		}
			
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
			
		DepositTradeModel::updateAll(['status' => 'SHIPPED'], ['bizOrderId' => $orderInfo['order_sn'], 'bizIdentity' => Def::TRADE_ORDER, 'seller_id' => $orderInfo['seller_id']]);
			
		$model = new OrderLogModel();
		$model->order_id = $orderInfo['order_id'];
		$model->operator = addslashes(Yii::$app->user->identity->username);
		$model->order_status = Def::getOrderStatus($orderInfo['status']);
		$model->changed_status = Def::getOrderStatus(Def::ORDER_SHIPPED);
		$model->remark = $post->remark ? $post->remark : '';
		$model->log_time = Timezone::gmtime();
		$model->save();
		
		if($sendNotify === true)
		{
			// 短信和邮件提醒： 卖家已发货通知买家
			Basewind::sendMailMsgNotify($orderInfo, array(
					'key' 		=> 'tobuyer_shipped_notify',
					'receiver' 	=> $orderInfo['buyer_id']
				),
				array(
					'key' 		=> 'tobuyer_shipped_notify',
					'receiver'  => $orderInfo['phone_mob'], // 收货人的手机号
				)
			);
		}
		return true;
	}
}
