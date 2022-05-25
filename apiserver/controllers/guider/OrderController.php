<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers\guider;

use Yii;
use yii\web\Controller;
use yii\helpers\ArrayHelper;

use common\models\OrderModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;
use common\library\Def;
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
	
	/* @获取团长订单管理列表数据
	 * @api 接口访问地址: http://api.xxx.com/guider/order/list
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
		$model = new \apiserver\models\OrderForm(['enter' => 'guider']);
		list($list, $page) = $model->formData($post);
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 团长更新订单状态
	 * @api 接口访问地址: http://api.xxx.com/guider/order/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id', 'status']);
		$post->order_id = $this->getOrderId($post);
		
		if(!$post->order_id) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('order_id_sn_empty'));
		}
		
		$query = OrderModel::find()->where(['guider_id' => Yii::$app->user->id, 'otype' => 'guidebuy', 'order_id' => $post->order_id]);
		if(!($record = $query->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_order'));
		}
		
		// 只接受目标为：通知取货的状态变更
		if(!isset($post->status) || !in_array($post->status, [Def::ORDER_DELIVERED])) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('unsupport_status'));
		}

		$model = new \apiserver\models\OrderForm();
		
		// 团长通知提货
		if($post->status == Def::ORDER_DELIVERED) {
			if(!$model->orderDelivered($post, ArrayHelper::toArray($record))) {
				return $respond->output(Respond::HANDLE_INVALID, $model->errors);
			}
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