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
use yii\captcha\CaptchaValidator;

use common\models\DepositTradeModel;
use common\models\CashcardModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Business;

/**
 * @Id DepositCardrechargeForm.php 2018.4.17 $
 * @author mosir
 */
class DepositCardrechargeForm extends Model
{
	public $tradeNo;
	public $errors;
	
	public function submit($post = null, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		// 暂存充值交易号
		$this->tradeNo = DepositTradeModel::genTradeNo();
		$cashcard = CashcardModel::find()->select('id,money')->where(['cardNo' => $post->cardNo])->one();

		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
		$depopay_type = Business::getInstance('depopay')->build('cardrecharge', (object)['card_id' => $cashcard->id]);
		$result = $depopay_type->submit(array(
			'trade_info' => array('userid' => Yii::$app->user->id, 'party_id' => 0, 'amount' => $cashcard->money),
			'extra_info' => array('tradeNo' => $this->tradeNo, 'bizOrderId' => $post->cardNo)
		));
		if(!$result) {
			$this->errors = $depopay_type->errors;
			return false;
		}
		return true;
	}

    public function valid($post = null)
    {
        if(empty($post->cardNo)) {
			$this->errors = Language::get('cardNo_empty');
			return false;
		}
		
		if(empty($post->password)) {
			$this->errors = Language::get('card_password_empty');
			return false;
		}

		// 接口暂时不启用验证码
		if (Basewind::getCurrentApp() != 'api') {
			$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
			if (!$captchaValidator->validate($post->captcha)) {
				$this->errors = Language::get('captcha_failed');
				return false;
			}
		}
		
		if(!CashcardModel::validateCard($post->cardNo, $post->password)) {
			$this->errors = Language::get('cashcard_verify_fail');
			return false;
		}
		
		$query = CashcardModel::find()->select('active_time,expire_time')->where(['cardNo' => $post->cardNo])->one();
		if(!$query) {
			$this->errors = Language::get('cardNo_invalid');
			return false;
		}
		if($query->active_time > 0) {
			$this->errors = Language::get('cashcard_already_used');
			return false;
		}
		if(($query->expire_time > 0) && ($query->expire_time <= Timezone::gmtime())) {
			$this->errors = Language::get('cashcard_already_expired');
			return false;
		}
		
		return true;
	}
}
