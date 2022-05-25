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
use common\library\Language;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id DefaultController.php 2018.10.13 $
 * @author yxyc
 */

class DefaultController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	public function init()
	{
		$this->params = [
			'homeUrl' 	=> Basewind::homeUrl(),
			'siteUrl'	=> Basewind::siteUrl()
		];
	}

	/**
	 * 获取站点配置参数(该接口为内部使用，不要开放)
	 * @api 接口访问地址: http://api.xxx.com/default/siteinfo
	 */
	public function actionSiteinfo()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		$this->params = [
			'frontendUrl' => $this->params['homeUrl'],
			'mobileUrl' => $this->params['siteUrl'],
			'site_name' => 'ShopWind多用户商城系统',//Yii::$app->params['site_name'],
			'site_desc' => 'ShopWind电商系统集成多种运营工具，社区团购、拼团系统、秒杀、搭配购、积分购，商家入驻、支付配送等',
			'site_logo' => Yii::$app->params['site_logo'] ? Formatter::path(Yii::$app->params['site_logo']) : Formatter::path(Yii::$app->params['default_site_logo']),
			'store_allow' => floatval(Yii::$app->params['store_allow'])
		];

		return $respond->output(true, null, $this->params);
	}
}
