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

use common\library\Basewind;;
use common\library\Promotool;

use apiserver\library\Respond;

/**
 * @Id FullpreferController.php 2018.12.8 $
 * @author yxyc
 */

class FullpreferController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取店铺满优惠信息
	 * @api 接口访问地址: http://api.xxx.com/fullprefer/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
	
		// 店铺满优惠
		$fullpreferTool = Promotool::getInstance('fullprefer')->build(['store_id' => Yii::$app->user->id]);
		
		if(($result = $fullpreferTool->getInfo())) {
			unset($result['rules']['type']);
			$result = array_merge(['status' => $result['status']], $result['rules']);
		}

		return $respond->output(true, null, $result);
	}

	/**
	 * 设置满优惠信息
	 * @api 接口访问地址: http://api.xxx.com/fullprefer/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['status']);

		$model = new \frontend\models\Seller_fullpreferForm(['store_id' => Yii::$app->user->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);		
	}
}