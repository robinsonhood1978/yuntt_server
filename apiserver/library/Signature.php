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

use common\library\Language;
use common\library\Timezone;

use apiserver\library\Respond;

/**
 * @Id Signature.php 2018.10.6 $
 * @author yxyc
 */

class Signature
{
	private $appid;
	private $appsecret;
	public $code;
	public $message;

	public function __construct()
	{
		// 取平台的值用于比对
		$this->appid = Yii::$app->params['apiserver']['appid'];
		$this->appsecret = Yii::$app->params['apiserver']['appsecret'];
	}

	/*
	 * 验证签名
	 */
	public function verify($post)
	{
		if (!$this->verifyData($post)) {
			return false;
		}

		$sign = $this->generateSign($post, $post['sign_type']);

		if ($sign != $post['sign']) {
			$this->code = Respond::SIGN_INVALID;
			$this->message = Language::get('sign_invalid');
			return false;
		}
		return true;
	}

	/*
	 *  生成签名
	 */
	private function generateSign($post, $sign_type = 'md5')
	{
		if (!$sign_type || (strtolower($sign_type) == 'md5')) {
			return $this->md5Sign($post);
		}

		// RSA签名日后拓展
		elseif (strtolower($sign_type == 'rsa')) {
			return $this->rsaSign($post);
		}
		return false;
	}

	/*
	 * MD5签名
	 */
	protected function md5Sign($post = array())
	{
		ksort($post);

		// 去掉不需要签名的字段
		foreach ($post as $key => $val) {
			if (!in_array($key, ['appid', 'version', 'sign_type', 'token', 'timestamp', 'format', 'pssl', 'params'])) {
				unset($post[$key]);
			}
		}

		$string = $this->getEncryptionString($post);
		return strtoupper(md5($string . $this->appsecret));
	}

	/*
	 * RSA签名
	 */
	private function rsaSign($post)
	{
		// TODO
		return false;
	}

	/*
	 * 验证系统级参数
	 */
	private function verifyData($post)
	{
		if (!isset($post['appid']) || empty($post['appid'])) {
			$this->code = Respond::APPID_EMPTY;
			$this->message = Language::get('appid_empty');
			return false;
		}
		if ($post['appid'] != $this->appid) {
			$this->code = Respond::APPID_INVALID;
			$this->message = Language::get('appid_invalid');
			return false;
		}

		// 目前只支持MD5签名，日后可拓展
		if (!isset($post['sign_type']) || (strtolower($post['sign_type']) != 'md5')) {
			$this->code = Respond::SIGNTYPE_INVALID;
			$this->message = Language::get('signtype_invalid');
			return false;
		}

		if (!isset($post['timestamp']) || empty($post['timestamp'])) {
			$this->code = Respond::TIMESTAMP_EMPTY;
			$this->message = Language::get('timestamp_empty');
			return false;
		}

		/*
		 * 请求时间戳
		 * @desc 服务端与客户端时间误差。默认30分钟，因为是签名认证，不用设置太长，确保在服务器与客户端时间差范围内即可
		 * @desc 此参数不是TOKEN的有效时间
		 * @desc 请考虑国外时间问题（苹果APP上架，审核人员无法访问接口可能跟这个有关）
		 */
		if (Timezone::gmstr2time($post['timestamp']) + 1800 <= Timezone::gmtime()) {
			$this->code = Respond::REQUEST_EXPIRE;
			$this->message = Language::get('timestamp_invalid');
			//return false;
		}

		if (!isset($post['sign']) || empty($post['sign'])) {
			$this->code = Respond::SIGN_EMPTY;
			$this->message = Language::get('sign_empty');
			return false;
		}

		return true;
	}

	/**
	 * 获取待加密的字符串
	 * @var pssl 业务参数是否参与加密（安全性更高，但也更消耗性能）
	 */
	protected function getEncryptionString($post)
	{
		$string = '';

		// 业务参数不参与加密
		if ($post['pssl'] == 'false') {
			foreach ($post as $key => $value) {
				if ($key != 'params') {
					$string .= $key . "=" . urlencode($value) . "&";
				}
			}
		}

		// 业务参数加密
		else {
			// 第二个参数可以处理不要把中文转成UNICODE for php >= 5.4
			$post['params'] = json_encode($this->rebrace($post['params']), JSON_UNESCAPED_UNICODE);

			foreach ($post as $key => $value) {
				// 不包括字节类型参数，如文件、字节流，剔除sign字段，剔除值为空的参数
				//if (false === $this->checkEmpty($value) && "@" != substr($value, 0, 1)) {
				if ("@" != substr($value, 0, 1)) { //判断是不是文件上传
					$value = $this->character($value);
					$string .= $key . "=" . urlencode($value) . "&";
				}
			}
		}
		return $string ? substr($string, 0, -1) : '';
	}

	/**
	 * 处理前端 params = {} 时，经过PHP JSON_ENCODE后变成 params = [] 导致签名串不一致的情形
	 */
	private function rebrace($params)
	{
		if (is_array($params)) {
			if (empty($params)) {
				$params = (object) array();
			} else {
				foreach ($params as $key => $value) {
					if (is_array($value)) {
						if (empty($value)) {
							$params[$key] = (object) array();
						}
					}
				}
			}
		}
		return $params;
	}

	protected function checkEmpty($value)
	{
		if (!isset($value))
			return true;
		if ($value === null)
			return true;
		if (is_string($value) && (trim($value) === ""))
			return true;

		return false;
	}

	/*
	 * 处理特殊字符
	 */
	protected function character($value)
	{
		// 过滤反斜杠
		$value = stripslashes($value);

		return $value;
	}
}
