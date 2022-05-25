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

use common\models\CouponModel;
use common\models\CouponsnModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id CouponController.php 2019.1.15 $
 * @author yxyc
 */

class CouponController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
 	 * 获取优惠券列表
	 * @api 接口访问地址: http://api.xxx.com/coupon/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'page', 'page_size']);
		
		$query = CouponModel::find()->alias('c')->select('c.coupon_id,c.coupon_name,c.coupon_value,c.min_amount,c.image,c.total,c.surplus,c.start_time,c.end_time,s.store_id,s.store_name,s.store_logo')
			->joinWith('store s', false)
			->where(['clickreceive' => 1, 'available' => 1])
			->andWhere(['>', 'c.end_time', Timezone::gmtime()])
			->andWhere(['or', ['total' => 0], ['and', ['>', 'total', 0], ['>', 'surplus', 0]]])
			->orderBy(['coupon_id' => SORT_DESC]);
		if(isset($post->store_id)) $query->andWhere(['c.store_id' => $post->store_id]);

		if(isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'c.coupon_id', explode(',', $post->items)]);
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['start_time'] = Timezone::localDate('Y-m-d H:i:s', $value['start_time']);
			$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
			$list[$key]['image'] = Formatter::path($value['image']);
			$list[$key]['store_logo'] = Formatter::path($value['store_logo'], 'store');
			$list[$key]['min_amount'] = floatval($value['min_amount']);
			$list[$key]['coupon_value'] = floatval($value['coupon_value']);
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
    }
	
	/**
 	 * 获取优惠券单条信息
	 * @api 接口访问地址: http://api.xxx.com/coupon/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['coupon_id']);
		
		$record = CouponModel::find()->alias('c')->select('c.coupon_id,c.coupon_name,c.coupon_value,c.min_amount,c.image,c.total,c.surplus,c.start_time,c.end_time,c.available,c.clickreceive,s.store_id,s.store_name,s.store_logo')
			->joinWith('store s', false)
			->where(['coupon_id' => $post->coupon_id])->asArray()->one();
		if(!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}
		$record['start_time'] = Timezone::localDate('Y-m-d H:i:s', $record['start_time']);
		$record['end_time'] = Timezone::localDate('Y-m-d H:i:s', $record['end_time']);
		$record['image'] = Formatter::path($record['image']);
		$record['store_logo'] = Formatter::path($record['store_logo'], 'store');
		$record['min_amount'] = floatval($record['min_amount']);
		$record['coupon_value'] = floatval($record['coupon_value']);
		
		return $respond->output(true, null, $record);
	}
	
	/**
 	 * 插入优惠券信息
	 * @api 接口访问地址: http://api.xxx.com/coupon/add
	 */
    public function actionAdd()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = new \frontend\models\Seller_couponForm(['store_id' => Yii::$app->user->id]);
   		if(($record = $model->save($post, true)) === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
 	 * 更新优惠券信息
	 * @api 接口访问地址: http://api.xxx.com/coupon/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['coupon_id']);

		$model = new \frontend\models\Seller_couponForm(['store_id' => Yii::$app->user->id, 'coupon_id' => $post->coupon_id]);
   		if(($record = $model->save($post, true)) === false) {
			return $respond->output(Respond::HANDLE_INVALID, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
 	 * 商家删除优惠券信息
	 * @api 接口访问地址: http://api.xxx.com/coupon/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			//return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['coupon_id']);

		if(!$post->coupon_id || !($model = CouponModel::find()->where(['coupon_id' => $post->coupon_id, 'store_id' => Yii::$app->user->id])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}

		// 失效了才可以删除
		if($model->available && ($model->end_time > Timezone::gmtime())) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('drop_disabled'));
		}
		if($model->delete() === false) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('drop_fail'));
		}

		return $respond->output(true);
	}
	
	/**
 	 * 点击领取优惠券
	 * @api 接口访问地址: http://api.xxx.com/coupon/receive
	 */
    public function actionReceive()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['coupon_id']);
		
		if(!($coupon = CouponModel::find()->where(['coupon_id' => $post->coupon_id, 'clickreceive' => 1, 'available' => 1])->andWhere(['>', 'end_time', Timezone::gmtime()])->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_coupon'));
		}
		if($coupon->store_id == Yii::$app->user->id) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('not_receive_self'));
		}
		if($coupon->total > 0 && $coupon->surplus <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('coupon_receive_all'));
		}

		if(CouponsnModel::find()->where(['userid' => Yii::$app->user->id, 'coupon_id' => $coupon->coupon_id])->andWhere(['>', 'remain_times', 0])->exists()) {
			return $respond->output(Respond::CURD_FAIL, Language::get('coupon_has_receive'));
		}
		
		$model = new CouponsnModel();
		$model->coupon_sn = $model->createRandom();
		$model->coupon_id = $coupon->coupon_id;
		$model->remain_times = 1;
		$model->userid = Yii::$app->user->id;
		if(!$model->save()) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}
		if(!CouponModel::updateAllCounters(['surplus' => -1],['coupon_id' => $coupon->coupon_id])){
			$model->delete();
			return $respond->output(Respond::CURD_FAIL, Language::get('receive_fail'));
		}
		return $respond->output(true, Language::get('receive_success'), ['coupon_sn' => $model->coupon_sn]);
	}
}