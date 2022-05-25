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

use common\models\DeliveryTemplateModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Plugin;

use apiserver\library\Respond;

/**
 * @Id DeliveryController.php 2018.10.20 $
 * @author yxyc
 */

class DeliveryController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 获取指定店铺的运费模板
	 * @api 接口访问地址: http://api.xxx.com/delivery/template
	 */
	public function actionTemplate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'goods_id']);

		// 运费模板列表
		$list = DeliveryTemplateModel::find()->where(['store_id' => $post->store_id])->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key] = DeliveryTemplateModel::formatTemplateForEdit($value);
		}

		return $respond->output(true, null, ['list' => $list]);
	}

	/**
	 * 获取快递公司列表
	 * @api 接口访问地址: http://api.xxx.com/delivery/company
	 */
	public function actionCompany()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		$model = Plugin::getInstance('express')->autoBuild();

		$list = [];
		if ($model) {
			$companys = $model->getCompanys();
			foreach ($companys as $key => $value) {
				$list[] = array('code' => $key, 'name' => $value);
			}
			// $this->params = ['plugin' => $model->getCode(), 'config' => $model->getConfig(), 'list' => $list];
		}
		return $respond->output(true, null, ['list' => $list]);
	}
}
