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

use common\models\IntegralModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;

use common\library\Timezone;
use common\library\Language;

/**
 * @Id My_integralSignForm.php 2018.9.19 $
 * @author mosir
 */
class My_integralSignForm extends Model
{
	public $errors = null;
	
	public function submit($post = null)
	{
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			$this->errors = Language::get('integral_disabled');
			return false;
		}
		
		$query = IntegralLogModel::find()->select('add_time')->where(['userid' => Yii::$app->user->id, 'type' => 'signin'])->orderBy(['log_id' => SORT_DESC])->one();
		if($query && Timezone::localDate('Ymd', Timezone::gmtime()) == Timezone::localDate('Ymd', $query->add_time)) {
			$this->errors = Language::get('have_get_integral_for_signin');
			return false;
		}
		
		$balance = $signAmount = 0;
		if(($signAmount = IntegralSettingModel::getSysSetting('signin')) <= 0) {
			$this->errors = Language::get('signin_amount_le0');
			return false;
		}
		if(($balance = IntegralModel::updateIntegral(['userid' => Yii::$app->user->id, 'type' => 'signin', 'amount' => $signAmount, 'flag' => sprintf(Language::get('signin_integral_flag'), $signAmount)])) === false) {
			$this->errors = Language::get('signin_integral_fail');
			return false;
		}
		return array($balance, $signAmount);
	}
}
