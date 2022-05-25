<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\UserModel;
use common\models\UserTokenModel;
use common\models\BindModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Def;
use common\library\Plugin;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id AuthController.php 2018.10.15 $
 * @author yxyc
 */

class AuthController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 获取访问TOKEN
	 * @api 接口访问地址: http://api.xxx.com/auth/token
	 */
	public function actionToken()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false, true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (!($result = $this->createToken($respond))) {
			return $respond->output(Respond::TOKEN_FAIL, Language::get('loginfail'));
		}
		return $respond->output(true, null, $result);
	}

	/**
	 * 获取用户登录TOKEN
	 * @api 接口访问地址: http://api.xxx.com/auth/login
	 */
	public function actionLogin()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false, true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if (in_array($post->logintype, ['weixin', 'qq', 'alipay', 'apple'])) {
			return $this->unilogin($respond, $post);
		}
		// 手机号加密码登录
		if ($post->logintype == 'password') {
			return $this->phonePasswordLogin($respond, $post);
		}
		// 手机号加短信验证码登录
		if ($post->logintype == 'verifycode') {
			return $this->phoneCodeLogin($respond, $post);
		}
	}

	/**
	 * UniAPP统一登录接口
	 * 兼容微信/QQ/支付宝/Apple登录或绑定
	 */
	private function unilogin($respond, $post)
	{
		$identity = null;

		$connect = Plugin::getInstance('connect')->build($post->scene ? $post->scene : $post->logintype, $post);
		if($connect->callback(true)) {
			$identity = UserModel::findOne($connect->userid);
		}

		if (!$identity || !($result = $this->createToken($respond, $identity))) {
			return $respond->output(Respond::TOKEN_FAIL, Language::get('loginfail'));
		}

		
		$result['user_info'] = $this->getUserInfo($identity);

		// $arr = [
		// 	'connect' => $connect,
		// 	'identity' => $identity,
		// 	'result' => $result,
		// ];

		return $respond->output(true, null, $result);
	}

	/**
	 * 手机号和密码登录
	 * @var $phone_mob|$password
	 */
	private function phonePasswordLogin($respond, $post)
	{
		if (!$post->phone_mob) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_required'));
		}
		if(!($identity = UserModel::find()->where(['phone_mob' => $post->phone_mob])->one())) {
			return $respond->output(Respond::USER_NOTEXIST, Language::get('no_such_user'));
		}
		if (!$identity->validatePassword($post->password)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('password_valid'));
		}
		if (!($result = $this->createToken($respond, $identity))) {
			return $respond->output(Respond::TOKEN_FAIL, Language::get('loginfail'));
		}
		$result['user_info'] = $this->getUserInfo($identity);
		return $respond->output(true, null, $result);
	}

	/**
	 * 手机号和短信登录/注册
	 * @var string $phone_mob|$verifycode
	 */
	private function phoneCodeLogin($respond, $post)
	{
		// 手机短信验证
		if (($smser = Plugin::getInstance('sms')->autoBuild())) {
			// 兼容微信session不同步问题
			if ($post->verifycodekey) {
				$smser->setSessionByCodekey($post->verifycodekey);
			}
			if (!Basewind::isPhone($post->phone_mob)) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_invalid'));
			}
			if (empty($post->verifycode) || (md5($post->phone_mob . $post->verifycode) != Yii::$app->session->get('phone_code'))) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_code_check_failed'));
			}
			if (Timezone::gmtime() - Yii::$app->session->get('last_send_time_phone_code') > 120) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_code_check_timeout'));
			}
			// 至此，短信验证码是正确的
			if (!($identity = UserModel::find()->where(['phone_mob' => $post->phone_mob])->one())) {
				if (!($identity = $this->createUser($post))) {
					return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
				}
			}

			if (!($result = $this->createToken($respond, $identity))) {
				return $respond->output(Respond::TOKEN_FAIL, Language::get('loginfail'));
			}
			$result['user_info'] = $this->getUserInfo($identity);
			return $respond->output(true, null, $result);
		}

		return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_fail'));
	}

	/**
	 * 生成TOKEN
	 * @desc 设置过期时间为7天
	 */
	private function createToken($respond, $identity = null)
	{
		$expire_time = Timezone::gmtime() + $respond->expired;
		if ($identity) {
			$token = md5($expire_time . $identity->userid . $identity->username . $expire_time . mt_rand(1000, 9999));
			if (!($model = UserTokenModel::find()->where(['userid' => $identity->userid])->one())) {
				$model = new UserTokenModel();
				$model->userid = $identity->userid;
			}
		} else {
			$model = new UserTokenModel();
			$model->userid = 0;
			$token = md5($expire_time . mt_rand(1000000, 9999999) . $expire_time . $respond->expired);
		}
		$model->token = $token;
		$model->expire_time = $expire_time;
		if (!$model->save()) {
			return false;
		}
		return ['token' => $token, 'expire_time' => Timezone::localDate('Y-m-d H:i:s', $expire_time)];
	}

	/**
	 * 返回给客户端的用户信息
	 */
	private function getUserInfo($identity)
	{
		if(!$identity) {
			return array();
		}
		$identity = ArrayHelper::toArray($identity);
		foreach ($identity as $key => $value) {
			if (!in_array($key, ['userid', 'username', 'phone_mob', 'nickname', 'portrait'])) {
				unset($identity[$key]);
			}
		}
		$identity['portrait'] = Formatter::path($identity['portrait'], 'portrait');

		// 查询是否有店铺
		if (StoreModel::find()->select('store_id')->where(['state' => Def::STORE_OPEN, 'store_id' => $identity['userid']])->exists()) {
			$identity['store_id'] = $identity['userid'];
		}

		return $identity;
	}

	/**
	 * 创建用户
	 * @param object $post
	 */
	private function createUser($post)
	{
		$model = new \frontend\models\UserRegisterForm();

		do {
			$model->username = UserModel::generateName($post->logintype);
			$model->password  = mt_rand(1000, 9999);
			$model->phone_mob = $post->phone_mob ? $post->phone_mob : '';
			$user = $model->register(['portrait' => $post->portrait ? $post->portrait : '', 'nickname' => $post->nickname ? $post->nickname : '']);
		} while (!$user);

		return $user;
	}
}
