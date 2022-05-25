<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\library;

use yii;

use yii\web\Response;

use common\models\UserModel;
use common\models\UserTokenModel;
use common\models\DistributeSettingModel;

use common\library\Timezone;
use common\library\Language;

use apiserver\library\Signature;

/**
 * @Id Respond.php 2018.10.6 $
 * @author yxyc
 */

class Respond
{
	private $post;
	private $code;
	private $message;

	// TOKEN过期时间
	public $expired = 604800;

	/*
	 * 返回的CODE值统一规范：
	 * CODE必须是数字型，非字符型，以下是系统级验证的CODE值
	 * 		0: 代表成功
	 *   1001：表示APPID为空
	 *   1002：表示APPID非法
	 *   1021：表示签名类型（sign_type)非法，目前仅支持MD5
	 *   1031：表示时间戳为空
	 *   1032：表示请求已失效
	 *   1041：表示签名（sign）为空
	 *   1042：表示签名（sign）不正确
	 *   2001：表示用户不存在
	 *   3001：表示要操作的数据不存在，比如查询/删除/更新操作，没有对应的数据
	 *   3002：表示请求的参数不能为空或参数不合法等导致的数据错误
	 *   3003：表示有相关数据，但在对数据进行查询/更新/删除的操作失败
	 *   3004：表示业务异常
	 *   4001：表示获取TOKEN失败
	 *   4002: 表示TOKEN无效
	 *   4003：表示TOKEN已过期
	 * 	 4004: 表示TOKEN无权限（用户无权限，或未登录）
	 */
	const SUCCESS 				= 0;
	const APPID_EMPTY 			= 1001;
	const APPID_INVALID			= 1002;
	const SIGNTYPE_INVALID 		= 1021;
	const TIMESTAMP_EMPTY 		= 1031;
	const REQUEST_EXPIRE 		= 1032;
	const SIGN_EMPTY 			= 1041;
	const SIGN_INVALID 			= 1042;
	const USER_NOTEXIST 		= 2001;
	const RECORD_NOTEXIST 		= 3001;
	const PARAMS_INVALID 		= 3002;
	const CURD_FAIL 			= 3003;
	const HANDLE_INVALID		= 3004;
	const TOKEN_FAIL			= 4001;
	const TOKEN_INVALID			= 4002;
	const TOKEN_EXPIRE			= 4003;
	const TOKEN_DISALLOW		= 4004;

	public function __construct()
	{
		$this->post = $this->input();
	}

	/**
	 * 此处可以充分考虑接收参数的各种格式进行拓展
	 * @desc 目前已通用接收 get,post,json
	 */
	public function input()
	{
		$post = Yii::$app->request->post();
		if (!$post) {
			$post = Yii::$app->request->get();
		}

		foreach ($post as $key => $val) {
			$post[$key] = urldecode($val);
		}
		$post['params'] = json_decode($post['params'], true);

		return $post;
	}

	/**
	 * 接口输出数据
	 */
	public function output($code, $message = '', $data = null)
	{
		if (!isset($this->post['format'])) $format = 'json';
		else $format = strtolower($this->post['format']);

		if ($format == 'xml') {
			Yii::$app->response->format = Response::FORMAT_XML;
		} else {
			Yii::$app->response->format = Response::FORMAT_JSON;
		}

		// 成功
		if ($code === true) {
			$this->code = self::SUCCESS;
			$this->message = $message ? $message : Language::get('request_ok');
		}

		// 抓取的系统级错误
		elseif ($code === false) {
			// 无需设置，已有值
		}
		// 抓取的业务级错误
		else {
			$this->code = $code;
			$this->message = $message ? $message : Language::get('handle_fail');
		}

		return ['code' => $this->code, 'message' => $this->message, 'data' => $data];
	}

	/**
	 * 验证系统级参数
	 * @param boolean $force 是否限制用户登录后才能访问接口
	 */
	public function verify($force = true, $auth = false)
	{
		$signature = new Signature();
		if (!$signature->verify($this->post)) {
			$this->code = $signature->code;
			$this->message = $signature->message;
			return false;
		}

		// 如果是获取TOKEN的请求
		if ($auth == true) {
			return true;
		}

		// 此时，签名认证通过，可以认为除TOKEN外所有参数的都是合法的
		// 接下来，验证访客，并设置访客状态
		return $this->verifyUser($force);
	}

	/** 
	 * 访客初始化
	 * 验证用户，并设置访客登录状态
	 * 当访问与用户相关的接口时，比如访问我的收货地址接口，必须是用户登录状态，才能正常返回数据
	 * 比如访问与用户无关的接口（获取类目信息，获取地区信息），则游客状态下，也仍然返回数据
	 * 如果不允许游客访问接口，则抛出错误
	 */
	public function verifyUser($force = true)
	{
		// 把无效的TOKEN先清掉
		UserTokenModel::deleteAll(['<=', 'expire_time', Timezone::gmtime()]);
		$query = UserTokenModel::find()->select('userid,expire_time')->where(['token' => $this->post['token']])->one();
		if (!$query) {
			$this->code = self::TOKEN_INVALID;
			$this->message = Language::get('token_invalid');
			return false;
		}
		if ($query->expire_time <= Timezone::gmtime()) {
			$this->code = self::TOKEN_EXPIRE;
			$this->message = Language::get('token_expire');
			return false;
		}

		$identity = UserModel::find()->where(['userid' => $query->userid])->one();
		if ($identity && Yii::$app->user->login($identity)) {
			
			// 修改过期时间，以达到延长登录时效的效果 (但是7天内没有登录的，会过期)
			$query->update(['expire_time' => Timezone::gmtime() + $this->expired]);

			// 分销功能，此处抓取访客的邀请并保存
			if(isset($this->post['params']['invite']) && ($invite = $this->post['params']['invite'])) {
				DistributeSettingModel::saveInvites($invite);
			}

		} else {
			Yii::$app->user->logout(); // 与客户端保持同步
			if ($force) {
				$this->code = self::TOKEN_DISALLOW;
				$this->message = Language::get('user_invalid');
				return false;
			}
		}
		return true;
	}

	/**
	 * 获取业务参数
	 */
	public function getParams()
	{
		return $this->post['params'];
	}

	public function verifyParams()
	{
		// TODO 
	}
}
