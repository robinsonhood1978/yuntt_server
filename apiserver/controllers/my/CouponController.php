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

use common\models\CouponsnModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id CouponController.php 2019.11.20 $
 * @author yxyc
 */

class CouponController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;
	
	public $params;
	
	/** 
	 * 获取我的优惠券列表
	 * @api 接口访问地址: http://api.xxx.com/my/coupon/list
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

		$query = CouponsnModel::find()->alias('cs')->select('cs.coupon_sn,cs.remain_times,c.coupon_id,c.coupon_name,c.coupon_value,c.start_time,c.end_time,c.min_amount,c.store_id')->joinWith('coupon c',false)->where(['and', ['>', 'c.coupon_id', 0],['userid' => Yii::$app->user->id]])->orderBy(['c.coupon_value' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value)
		{
			if($value['store_id']) {
				$store = StoreModel::find()->select('store_name,store_logo')->where(['store_id' => $value['store_id']])->asArray()->one();
				if($store) {
					$store['store_logo'] = Formatter::path($store['store_logo'], 'store');
					$list[$key] += $store;
				}
			}
	
			if(($value['remain_times'] < 1) || ($value['end_time'] > 0 && $value['end_time'] < Timezone::gmtime())) {
				$list[$key]['available'] = 0;
			}

			$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}

	/**
	 * 获取我的优惠券的数量
	 * @api 接口访问地址: http://api.xxx.com/my/coupon/quantity
	 */
    public function actionQuantity()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$query = CouponsnModel::find()->where(['userid' => Yii::$app->user->id]);
		$this->params = ['quantity' => $query->count()];

		return $respond->output(true, null, $this->params);
    }
}