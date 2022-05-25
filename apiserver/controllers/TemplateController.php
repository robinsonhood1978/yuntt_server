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
 * @Id TemplateController.php 2018.10.13 $
 * @author yxyc
 */

class TemplateController extends Controller
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
	 * 获取模板拖拽模块配置信息(该接口为内部使用，不要开放)
	 * @api 接口访问地址: http://api.xxx.com/template/block
	 */
	public function actionBlock()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		if(!isset($post->page) || empty($post->page)) {
			$post->page = 'index';
		}

		//  读取手机端的配置文件
		$config = require_once(Yii::getAlias('@mobile') . "/web/data/page_config/default.{$post->page}.config.php");

		if (!$config || !is_array($config)) {
			return $respond->output(true, null, []);
		}

		$setting = array();
		foreach ($config['config'] as $list) {
			foreach ($list as $value) {
				$setting[] = $config['widgets'][$value];
			}
		}

		// 处理里面的图片路径问题
		$this->strReplace($setting, 'data/files', Basewind::homeUrl() .  '/data/files');
		if ($post->block) {
			foreach ($setting as $key => $value) {
				if ($value['name'] == $post->block) {
					$setting = $value;
					break;
				}
			}
		}

		return $respond->output(true, null, $setting);
	}

	/**
	 * 将配置文件中的图片路径转为绝对路径
	 */
	private function strReplace(&$array, $search, $replace)
	{
		$array = str_replace($search, $replace, $array);
		if (is_array($array)) {
			foreach ($array as $key => $val) {
				if (is_array($val)) {
					$this->strReplace($array[$key], $search, $replace);
				}
			}
		}
	}
}
