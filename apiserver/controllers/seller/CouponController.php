<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers\seller;

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
	 * @api 接口访问地址: http://api.xxx.com/seller/coupon/list
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
		
		$query = CouponModel::find()->alias('c')->select('c.coupon_id,c.coupon_name,c.coupon_value,c.min_amount,c.image,c.total,c.surplus,c.start_time,c.end_time,c.available,s.store_id,s.store_name,s.store_logo')
			->joinWith('store s', false)
			->where(['c.store_id' => Yii::$app->user->id])
			->orderBy(['coupon_id' => SORT_DESC]);
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['start_time'] = Timezone::localDate('Y-m-d H:i:s', $value['start_time']);
			$list[$key]['end_time'] = Timezone::localDate('Y-m-d H:i:s', $value['end_time']);
			$list[$key]['image'] = Formatter::path($value['image']);
			$list[$key]['store_logo'] = Formatter::path($value['store_logo'], 'store');
			$list[$key]['min_amount'] = floatval($value['min_amount']);
			$list[$key]['coupon_value'] = floatval($value['coupon_value']);

			if($value['end_time'] > 0 && $value['end_time'] < Timezone::gmtime()) {
				$list[$key]['available'] = 0;
			}
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
    }
}