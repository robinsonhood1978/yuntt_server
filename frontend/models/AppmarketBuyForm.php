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

use common\models\AppmarketModel;
use common\models\ApprenewalModel;
use common\models\AppbuylogModel;

use common\library\Timezone;
use common\library\Language;
use common\library\Def;

/**
 * @Id AppmarketBuyForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketBuyForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		// 关闭掉没有付款成功且超过1天的订单
		$this->tradeClosed();
		
		if(!$post->id || !($appmarket = AppmarketModel::find()->select('aid,appid,status,price')->where(['aid' => $post->id])->one())) {
			$this->errors = Language::get('app_not_existed');
			return false;	
		}

		if(!$appmarket->status) {
			$this->errors = Language::get('app_unavailable');
			return false;
		}
		
		// 目前所有应用都是卖家才能购买
		if(!$this->store_id) {
			$this->errors = Language::get('visitor_must_seller');
			return false;
		}
		
		// 计算需要支付的金额
		$amount = round($appmarket->price * $post->period, 2);
		
		// 为避免用户无数次的购买免费的应用，在这里做个控制：如果上次购买的应用是免费的，且离到期时间大于一个月，那么不允许购买
		if($amount == 0)
		{
			$appbuylog = AppbuylogModel::find()->select('amount')->where(['userid' => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED, 'appid' => $appmarket->appid])->orderBy(['bid' => SORT_DESC])->one();
			
			if($appbuylog && ($appbuylog->amount == 0)) {
				$apprenewal = ApprenewalModel::find()->select('expired')->where(['userid' => Yii::$app->user->id, 'appid' => $appmarket->appid])->one();
				
				// 如果到期时间大于一个月（一个月以30天计算），则不允许再购买免费应用了
				if($apprenewal && ($apprenewal->expired - Timezone::gmtime() > 30 * 24 * 3600)) {
					$this->errors = Language::get('not_allow_buy_for_often');
					return false;
				}
			}
		}
		
		// 如果没有加入到购物车，则先加入
		if(!($model = AppbuylogModel::find()->where(['userid' => Yii::$app->user->id, 'status' => Def::ORDER_PENDING, 'appid' => $appmarket->appid, 'period' => $post->period, 'amount' => $amount])->one())) {
		
			$model = new AppbuylogModel();
			$model->orderId = AppbuylogModel::genOrderId();
			$model->appid = $appmarket->appid;
			$model->userid = Yii::$app->user->id;
			$model->period = $post->period;
			$model->amount = $amount;
			$model->status = Def::ORDER_PENDING;
			$model->add_time = Timezone::gmtime();
			if(!$model->save()) {
				$this->errors = $model->errors ? $model->errors : Language::get('add_cart_fail');
				return false;
			}
		}
		return $model;
	}
	
	/* 关闭掉没有付款成功且超过1天的订单 */
	private function tradeClosed()
	{
		AppbuylogModel::updateAll(['status' => Def::ORDER_CANCELED], ['and', ['userid' => Yii::$app->user->id, 'status' => Def::ORDER_PENDING], ['<=', 'add_time', Timezone::gmtime() - 1 * 24 * 3600]]);
	}
}
