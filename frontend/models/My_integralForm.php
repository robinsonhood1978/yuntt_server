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

use common\models\IntegralModel;
use common\models\OrderModel;
use common\models\OrderIntegralModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_integralForm.php 2018.9.19 $
 * @author mosir
 */
class My_integralForm extends Model
{
	public $errors = null;
	
	/**
	 * 获取会员积分信息
	 * @desc API 接口使用到该数据
	 */
	public function formData($post = null)
	{
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			$this->errors = Language::get('integral_disabled');
			return false;
		}

		if(Yii::$app->user->isGuest) {
			$this->errors = Language::get('login_please');
			return false;
		}
		
		// 会员当前的可用积分
		$integral = IntegralModel::find()->where(['userid' => Yii::$app->user->id])->asArray()->one();
		if(!$integral) {
			$integral = ArrayHelper::toArray(IntegralModel::createAccount(Yii::$app->user->id));
		}
		
		// 会员当前被冻结的积分
		if(($frozen_integral = OrderIntegralModel::find()->where(['buyer_id' => Yii::$app->user->id])->sum('frozen_integral'))) {
			$integral['frozen_integral'] = $frozen_integral;
		}		
		// 今天是否可以签到领积分（每天一次）
		if(($signAmount = IntegralSettingModel::getSysSetting('signin')) > 0) {
			$integral['signinabled'] = true;
			$integral['signIntegral'] = $signAmount; 
			
			$query = IntegralLogModel::find()->select('add_time')->where(['type' => 'signin', 'userid' => Yii::$app->user->id])->orderBy(['log_id' => SORT_DESC])->one();
			if($query && Timezone::localDate('Ymd', Timezone::gmtime()) == Timezone::localDate('Ymd', $query->add_time)) {
				$integral['signined'] = true;
			}
		}
		return $integral;
	}
	
	// for PC
	public function getLogs($post = null, $pageper = 4)
	{
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			$this->errors = Language::get('integral_disabled');
			return false;
		}
		
		$query = IntegralLogModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['log_id' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($list as $key => $value)
		{
			$list[$key]['state'] = IntegralModel::getStatusLabel($value['state']);
			$list[$key]['name'] = Language::get($value['type']);
			if($value['order_id']) {
				$list[$key]['order_sn'] = OrderModel::find()->select('order_sn')->where(['order_id' => $value['order_id']])->scalar();
			}
		}
		
		return array($list, $page);
	}
}
