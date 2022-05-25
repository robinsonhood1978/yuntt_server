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

use common\models\UserModel;
use common\models\OrderModel;
use common\models\DepositAccountModel;
use common\models\DepositTradeModel;
use common\models\DepositRecordModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id DepositController.php 2018.11.15 $
 * @author yxyc
 */

class DepositController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 读取用户资产信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$record = UserModel::find()->alias('u')->select('u.userid,u.username,da.account,da.money,da.nodrawal,da.frozen,da.pay_status')->joinWith('depositAccount da', false)->where(['u.userid' => Yii::$app->user->id])->asArray()->one();
		$record['pay_status'] = $record['pay_status'] == 'ON' ? 1 : 0;
		return $respond->output(true, null, $record);
	}

	/**
	 * 更新用户资产信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['pay_status']);
		$post->pay_status = $post->pay_status ? 'ON' : 'OFF';
		$post->code = $post->verifycode;

		$model = new \frontend\models\DepositConfigForm();
		if (!$model->save($post, true)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		return $respond->output(true, null);

		// 手机短信验证
		// if (($smser = Plugin::getInstance('sms')->autoBuild())) {

		// 	// 兼容微信session不同步问题
		// 	if ($post->verifycodekey) {
		// 		$smser->setSessionByCodekey($post->verifycodekey);
		// 	}
		// 	$model = new \frontend\models\DepositConfigForm();
		// 	if (!$model->save($post, true)) {
		// 		return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		// 	}
		// 	return $respond->output(true, null);
		// }
		return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
	}

	/**
	 * 获取交易记录信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/tradelist
	 */
	public function actionTradelist()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['frozen', 'page', 'page_size']);

		$query = DepositTradeModel::find()->select('trade_id,tradeNo,outTradeNo,payTradeNo,bizOrderId,bizIdentity,buyer_id,seller_id,amount,status,payment_code,flow,title,buyer_remark,seller_remark,add_time,pay_time,end_time')
			->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])
			->orderBy(['trade_id' => SORT_DESC]);

		// 如果查询的是冻结的记录
		// 目前只冻结待审核的提现，如果还有其他类型的冻结交易，则加到此
		if ($post->frozen) {
			$query->andwhere(['tradeCat' => 'WITHDRAW', 'status' => 'WAIT_ADMIN_VERIFY']);
		}
		if($post->bizIdentity) {
			$query->andWhere(['bizIdentity' => $post->bizIdentity]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			if ($value['seller_id'] == Yii::$app->user->id) {
				$list[$key]['flow'] = ($value['flow'] == 'income') ?  'outlay' : 'income';
			}
			$list[$key] = $this->formatDate($value);
			$list[$key]['payment_name'] = Language::get($value['payment_code']);

			if ($value['buyer_id']) {
				$portrait = UserModel::find()->select('portrait')->where(['userid' => $value['buyer_id']])->scalar();
				$list[$key]['buyer_portrait'] = Formatter::path($portrait, 'portrait');
			}
			if ($value['seller_id']) {
				$portrait = UserModel::find()->select('portrait')->where(['userid' => $value['seller_id']])->scalar();
				$list[$key]['seller_portrait'] = Formatter::path($portrait, 'portrait');
			}
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取交易单条记录信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/trade
	 */
	public function actionTrade()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['trade_id']);

		if (!isset($post->trade_id) && !isset($post->tradeNo)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('trade_id and tradeNo empty'));
		}
		$query = DepositTradeModel::find()->select('trade_id,tradeNo,outTradeNo,payTradeNo,bizOrderId,bizIdentity,buyer_id,seller_id,amount,status,payment_code,flow,title,buyer_remark,seller_remark,add_time,pay_time,end_time')->where(['or', ['buyer_id' => Yii::$app->user->id], ['seller_id' => Yii::$app->user->id]])->orderBy(['trade_id' => SORT_DESC]);
		if (isset($post->trade_id)) {
			$query->andWhere(['trade_id' => $post->trade_id]);
		}
		if (isset($post->tradeNo)) {
			$query->andWhere(['tradeNo' => $post->tradeNo]);
		}
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('trade record empty'));
		}
		if ($record['seller_id'] == Yii::$app->user->id) {
			$record['flow'] = ($record['flow'] == 'income') ?  'outlay' : 'income';
		}
		// 商品订单，读取商品信息
		if ($record['bizIdentity'] == Def::TRADE_ORDER) {
			$orderInfo = OrderModel::find()->select('order_id,order_sn,order_amount')->where(['order_sn' => $record['bizOrderId']])->with(['orderGoods' => function ($model) {
				$model->select('rec_id,spec_id,order_id,goods_id,goods_name,goods_image,specification,price,quantity');
			}])->asArray()->one();
			if ($orderInfo) {
				foreach ($orderInfo['orderGoods'] as $key => $value) {
					$orderInfo['orderGoods'][$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
				}
			}
			$record['orderInfo'] = $orderInfo;
		}

		$record = $this->formatDate($record);
		$record['payment_name'] = Language::get($record['payment_code']);

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取收支明细列表信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/recordlist
	 */
	public function actionRecordlist()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = DepositRecordModel::find()->alias('dr')->select('dr.record_id,dr.userid,dr.amount,dr.balance,dr.flow,dr.tradeTypeName as title,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,dt.add_time,dt.pay_time,dt.end_time,u.username,u.nickname,u.portrait')->joinWith('depositTrade dt', false)->joinWith('user u', false)
			->where(['dr.userid' => Yii::$app->user->id])->orderBy(['record_id' => SORT_DESC]);
		if($post->bizIdentity) {
			$query->andWhere(['bizIdentity' => $post->bizIdentity]);
		}
		if($post->flow) {
			$query->andWhere(['dr.flow' => $post->flow]);
		}
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key] = $this->formatDate($value);
			$list[$key]['portrait'] = Formatter::path($value['portrait'], 'portrait');
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取收支明细单条信息
	 * @api 接口访问地址: http://api.xxx.com/deposit/record
	 */
	public function actionRecord()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['record_id']);

		$query = DepositRecordModel::find()->alias('dr')->select('dr.record_id,dr.userid,dr.amount,dr.balance,dr.flow,dr.tradeTypeName as title,dt.tradeNo,dt.bizOrderId,dt.bizIdentity,dt.add_time,dt.pay_time,dt.end_time,u.username,u.nickname,u.portrait')->joinWith('depositTrade dt', false)->joinWith('user u', false)->where(['and', ['dr.userid' => Yii::$app->user->id], ['record_id' => $post->record_id]])->orderBy(['record_id' => SORT_DESC]);
		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no such record'));
		}

		$record = $this->formatDate($record);
		$record['portrait'] = Formatter::path($record['portrait'], 'portrait');

		return $respond->output(true, null, $record);
	}

	/**
	 * 充值到余额
	 * @api 接口访问地址: http://api.xxx.com/deposit/recharge
	 */
	public function actionRecharge()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = new \frontend\models\DepositRechargeForm();
		list($tradeNo, $payment_code) = $model->formData($post);
		if ($model->errors) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// 获取交易数据
		list($errorMsg, $orderInfo) = DepositTradeModel::checkAndGetTradeInfo($tradeNo, Yii::$app->user->id);
		if ($errorMsg !== false) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		// 生成支付参数
		list($payTradeNo, $payform) = Plugin::getInstance('payment')->build($payment_code, $post)->getPayform($orderInfo, false);
		$this->params = array_merge($payform, ['payTradeNo' => $payTradeNo]);
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 提现至银行卡
	 * @api 接口访问地址: http://api.xxx.com/deposit/drawal
	 */
	public function actionDrawal()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['bid']);

		$model = new \frontend\models\DepositWithdrawconfirmForm();
		if (!$model->save($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		return $respond->output(true);
	}

	/**
	 * 格式化时间
	 */
	private function formatDate($record)
	{
		$fields = ['add_time', 'pay_time', 'end_time'];
		foreach ($fields as $field) {
			isset($record[$field]) && $record[$field] = Timezone::localDate('Y-m-d H:i:s', $record[$field]);
		}
		return $record;
	}
}
