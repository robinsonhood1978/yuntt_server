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

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\RefundMessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use apiserver\library\Respond;

/**
 * @Id RefundController.php 2019.11.20 $
 * @author yxyc
 */

class RefundController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;
	
	/**
	 * 获取所有退款列表数据
	 * @api 接口访问地址: http://api.xxx.com/refund/list
	 */
    public function actionList()
	{
		// TODO
	}
	
	/**
	 * 获取退款单条信息
	 * @api 接口访问地址: http://api.xxx.com/refund/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);
		
		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}
		$query = RefundModel::find()->alias('r')->select('r.*,dt.bizOrderId,dt.bizIdentity')->where(['or', ['r.buyer_id' => Yii::$app->user->id], ['r.seller_id' => Yii::$app->user->id]])->andWhere(['r.refund_id' => $post->id])->joinWith('depositTrade dt', false);
		if(!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_refund'));
		}
		if(($record['bizIdentity'] == Def::TRADE_ORDER) && !empty($record['bizOrderId'])) {
			$record['order_sn'] = $record['bizOrderId'];
			$record['order_id'] = OrderModel::find()->select('order_id')->where(['order_sn' => $record['order_sn']])->scalar();
		}
		unset($record['bizIdentity'], $record['bizOrderId']);

		$record['created'] = Timezone::localDate('Y-m-d H:i:s', $record['created']);
		if($record['end_time']) {
			$record['end_time'] = Timezone::localDate('Y-m-d H:i:s', $record['end_time']);
		}

		return $respond->output(true, null, $record);
	}
	
	/**
	 * 买家创建退款申请
	 * @api 接口访问地址: http://api.xxx.com/refund/create
	 */
	public function actionCreate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['order_id', 'shipped']);
		
		$model = new \frontend\models\RefundForm();
		list($refund) = $model->getData($post, false);
		if(!$refund) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		$post = $this->computedRefundFee($post, $refund);

		$refund = $model->save($post, $post, true, false);
		if($refund === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true, null, ['refund_id' => $refund->refund_id, 'refund_sn' => $refund->refund_sn]);
	}
	
	/**
	 * 更新退款信息
	 * @api 接口访问地址: http://api.xxx.com/refund/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id', 'shipped']);
		
		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}

		$model = new \frontend\models\RefundForm();
		list($refund) = $model->getData($post, false);
		if(!$refund) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		$post = $this->computedRefundFee($post, $refund);

		$refund = $model->save($post, $post, true, false);
		if($refund === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true, null, ArrayHelper::toArray($refund));
	}

	/**
	 * 取消退款申请
	 * @api 接口访问地址: http://api.xxx.com/refund/cancel
	 */
	public function actionCancel()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);
		
		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}

		if(!($refund = RefundModel::find()->select('refund_id')->where(['refund_id' => $post->id])->andWhere(['buyer_id' => Yii::$app->user->id])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->one())) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('cancel_disallow'));
		}
		
		if(RefundModel::deleteAll(['refund_id' => $refund->refund_id])) {
			RefundMessageModel::deleteAll(['refund_id' => $refund->refund_id]);
		}

		return $respond->output(true);
	}

	/**
	 * 卖家同意退款申请
	 * @api 接口访问地址: http://api.xxx.com/refund/agree
	 */
	public function actionAgree()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);
		
		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}
		
		$model = new \frontend\models\RefundAgreeForm();
		if(!$model->submit($post, false)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		$record = RefundModel::find()->select('status,end_time')->where(['refund_id' => $post->id])->asArray()->one();

		return $respond->output(true, null, $record);
	}

	/**
	 * 卖家拒绝退款申请
	 * @api 接口访问地址: http://api.xxx.com/refund/refuse
	 */
	public function actionRefuse()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);
		
		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}
		
		$model = new \frontend\models\RefundRefuseForm();
		if(!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		$record = RefundModel::find()->select('status')->where(['refund_id' => $post->id])->asArray()->one();

		return $respond->output(true, null, $record);
	}

	/**
	 * 买家要求平台介入处理退款争议
	 * @api 接口访问地址: http://api.xxx.com/refund/intervene
	 */
	public function actionIntervene()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);

		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}
		
		$model = new \frontend\models\RefundInterveneForm();
		if(!$model->save($post, true)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}
		return $respond->output(true);
	}

	/**
	 * 获取退款留言记录
	 * @api 接口访问地址: http://api.xxx.com/refund/logs
	 */
	public function actionLogs()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id', 'page', 'page_size']);

		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}

		$model = new \frontend\models\RefundMessageForm();
		list($list, $page) = $model->formData($post, $post->page_size, false, $post->page);
		
		$this->params = array('list' => $list, 'pagination' => Page::formatPage($page, false));
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 提交退款留言记录
	 * @api 接口访问地址: http://api.xxx.com/refund/message
	 */
	public function actionMessage()
	{
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['refund_id']);

		// 接口兼容处理
		if(!($post->id = $this->getRefundId($post))) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('refund_id_sn_empty'));
		}

		$model = new \frontend\models\RefundMessageForm();
		if(!$model->save($post)) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}
	
	private function getRefundId($post)
	{
		if(isset($post->refund_id) && $post->refund_id) {
			return $post->refund_id;
		}
		if(isset($post->refund_sn) && !empty($post->refund_sn)) {
			return RefundModel::find()->select('refund_id')->where(['refund_sn' => $post->refund_sn])->scalar();
		}
		return false;
	}

	/**
	 * 计算退款金额的各个字段值
	 * 有可能API接口只提交一个退款金额的字段，需要通过该方法计算其他字段的值
	 */
	private function computedRefundFee($post, $refund = array())
	{
		if($post->refund_total_fee > $refund['goods_fee']) {
			$post->refund_goods_fee = $refund['goods_fee'];
			$post->refund_shipping_fee = $post->refund_total_fee - $refund['goods_fee'];
		} else {
			$post->refund_goods_fee = $post->refund_total_fee;
			$post->refund_shipping_fee = 0;
		}
		
		return $post;
	}
}