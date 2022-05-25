<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers\my;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;

use common\library\Basewind;
use common\library\Page;
use common\library\Taskqueue;

use apiserver\library\Respond;

/**
 * @Id OrderController.php 2019.11.20 $
 * @author yxyc
 */

class OrderController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;
	
	public $params;

	public function init()
	{
		Taskqueue::run();
	}
	
	/** 
	 * 获取我的订单列表数据
	 * @api 接口访问地址: http://api.xxx.com/my/order/list
	 */
    public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		$model = new \apiserver\models\OrderForm(['enter' => 'buyer']);
		list($list, $page) = $model->formData($post);
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/** 
	 * 获取我的订单提醒数据
	 * @api 接口访问地址: http://api.xxx.com/my/order/remind
	 */
    public function actionRemind()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		$model = new \frontend\models\UserForm();
		$this->params = $model->getBuyerStat();

		return $respond->output(true, null, $this->params);
	}

	/** 
	 * 我对订单的评价
	 * @api 接口访问地址: http://api.xxx.com/my/order/evaluate
	 */
    public function actionEvaluate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id']);
		$post->order_id = $this->getOrderId($post);

		$model = new \frontend\models\Buyer_orderEvaluateForm();
		if(!($orderInfo = $model->formData($post))) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		
		if(!$model->submit(ArrayHelper::toArray($post), $orderInfo)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}

	private function getOrderId($post)
	{
		if(isset($post->order_id)) {
			return $post->order_id;
		}

		if(isset($post->order_sn) && !empty($post->order_sn)) {
			return OrderModel::find()->select('order_id')->where(['order_sn' => $post->order_sn])->scalar();
		}

		return 0;
	}
}