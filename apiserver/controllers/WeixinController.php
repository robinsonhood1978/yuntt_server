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

use common\library\Basewind;
use common\library\Weixin;

use apiserver\library\Respond;

/**
 * @Id WeixinController.php 2018.12.25 $
 * @author yxyc
 */

class WeixinController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;

	/**
	 * 获取H5端微信分享的签名
	 * @api 接口访问地址: http://api.xxx.com/weixin/signature
	 */
	public function actionSignature()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$result = Weixin::getInstance()->GetSignPackage($post);
		if($result) {
		    $result = json_decode($result);
		}
		return $respond->output(true, null, $result);
	}
	
	/**
	 * 获取小程序的scheme码
	 * @api 接口访问地址: http://api.xxx.com/weixin/scheme
	 * 适用于短信、邮件、外部网页、微信内等拉起小程序的业务场景，获得到期失效和永久有效的小程序码
	 * 生成的 URL Scheme 形式如：weixin://dl/business/?t= *TICKET*
	 * iOS系统支持识别 URL Scheme，Android系统不支持直接识别 URL Scheme，用户无法通过 Scheme 正常打开小程序，开发者需要使用 H5 页面中转，再跳转到 Scheme 实现打开小程序，跳转代码示例如下：location.href = 'weixin://dl/business/?t= *TICKET*'
	 * @api https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/url-scheme/urlscheme.generate.html
	 */
	public function actionScheme()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 暂不采用该方案
	}

	/**
	 * 获取小程序 URL Link
	 * @api 接口访问地址: http://api.xxx.com/weixin/mpurl
	 * 适用于短信、邮件、网页、微信内等拉起小程序的业务场景，获得到期失效和永久有效的小程序链接
	 * 生成的 URL Link 形式如：https://wxaurl.cn/*TICKET* 或 https://wxmpurl.cn/*TICKET*
	 * 在微信内或者安卓手机打开 URL Link 时，默认会先跳转官方 H5 中间页，如果需要定制 H5 内容，可以使用云开发静态网站
	 * https://developers.weixin.qq.com/miniprogram/dev/api-backend/open-api/url-link/urllink.generate.html
	 */
	public function actionMpurl()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		list($post->path, $query) = $this->pathQuery($post, false);
		$response = Weixin::getInstance(null, 0, 'applet')->getWxalink(['path' => $post->path, 'query' => $query]);
		$result = json_decode($response);
		if($result->errcode) {
			return $respond->output(Respond::PARAMS_INVALID, $result->errmsg);
		}

		return $respond->output(true, null, $result->url_link);
	}

	/**
	 * 获取适用于小程序的路由和请求参数
	 */
	private function pathQuery($post, $removeslash = true)
	{
		if(!isset($post->page) || empty($post->page)) {
			return array('', '');
		}

		if($removeslash) {
			if(substr($post->page, 0, 1) == '/') {
				$post->page = substr($post->page, 1);
			}
		}

		$array = explode('?', $post->page);
		if(count($array) < 2) {
			$array[] = '';
		}

		return $array;
	}
}