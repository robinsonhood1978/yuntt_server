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
use common\models\DepositAccountModel;
use common\models\DepositSettingModel;

use common\library\Language;
use common\library\Business;

/**
 * @Id DepositTransferconfirmForm.php 2018.9.30 $
 * @author mosir
 */
class DepositTransferconfirmForm extends Model
{
	public $account = null;
	public $money = null;
	public $errors = null;

	public function valid($post)
	{
		$query = DepositAccountModel::find()->select('money,account,pay_status')->where(['userid' => Yii::$app->user->id])->one();
		if($query->pay_status != 'ON') {
			$this->errors = Language::get('pay_status_off');
			return false;
		}
		if($query->account == $post->account) {
			$this->errors = Language::get('select_account_yourself');
			return false;
		}
		if(!DepositAccountModel::find()->select('userid,account')->where(['account' => $post->account])->exists()) {
			$this->errors = Language::get('select_account_not_exist');
			return false;
		}
		// 验证转账金额
		if(empty($post->money) || !is_numeric($post->money) || ($post->money <= 0)) {
			$this->errors = Language::get('money_error');
			return false;
		}
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
			
		$captchaValidator = new CaptchaValidator(['captchaAction' => 'default/captcha']);
		if(!$captchaValidator->validate($post->captcha)) {
			$this->errors = Language::get('captcha_failed');
			return false;
		}
			
		if(!DepositAccountModel::checkAccountPassword($post->password, Yii::$app->user->id)) {
			$this->errors = Language::get('password_error');
			return false;
		}
		
		// 获取对方账户信息
		$party = $this->getPartyInfo($post);
		
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入
		$depopay_type = Business::getInstance('depopay')->build('transfer', $post);
		$result = $depopay_type->submit(array(
			'trade_info' => array('userid' => Yii::$app->user->id, 'party_id' => $party['userid'], 'amount' => $post->money, 'fee' => $this->getTransferFee($post)),
			'extra_info' => array('tradeNo' => DepositTradeModel::genTradeNo())
		));
			
		if(!$result) {
			$this->errors = $depopay_type->errors;
			return false;
		}
		return true;
	}
	
	/* 获取对方账户信息 */
	public function getPartyInfo($post = null)
	{
		$party = DepositAccountModel::find()->alias('da')->select('da.userid,da.account,u.portrait')->joinWith('user u', false)->where(['account' => $post->account])->asArray()->one();
		if(empty($party['portrait'])) $party['portrait'] = Yii::$app->params['default_user_portrait'];
		
		return $party;
	}
	/* 转账手续费 */
	public function getTransferFee($post = null)
	{
		$fee = round($post->money * DepositSettingModel::getDepositSetting(Yii::$app->user->id, 'transfer_rate'), 2);
		return $fee;
	}
}
