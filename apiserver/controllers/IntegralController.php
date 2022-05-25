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

use common\models\GoodsModel;
use common\models\OrderModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;
use common\models\GuideshopModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id IntegralController.php 2018.10.15 $
 * @author yxyc
 */

class IntegralController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	
	/**
	 * 获取积分用户列表
	 * @api 接口访问地址: http://api.xxx.com/integral/user
	 */
    public function actionUser()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		
		$query = IntegralModel::find()->alias('i')->select('i.amount,u.userid,u.username')->joinWith('user u', false)->indexBy('userid')->orderBy(['u.userid' => SORT_ASC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 获取当前用户积分信息
	 * @api 接口访问地址: http://api.xxx.com/integral/read
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
		
		$model = new \frontend\models\My_integralForm();
		if(($record = $model->formData($post)) === false) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}

		if(isset($record['frozen_integral'])) {
			$record['frozen'] = $record['frozen_integral'];
			unset($record['frozen_integral']);
		} else $record['frozen'] = 0;

		return $respond->output(true, null, $record);
    }
	
	/**
	 * 获取积分商品列表
	 * @api 接口访问地址: http://api.xxx.com/integral/goods
	 */
    public function actionGoods()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
			
		$query = GoodsModel::find()->alias('g')->select('g.goods_id,g.default_image as goods_image,g.goods_name,g.price,g.add_time,gi.max_exchange,s.store_id,s.store_name,gst.sales')->joinWith('goodsIntegral gi', false)->joinWith('store s', false)->joinWith('goodsStatistics gst', false)->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['>', 'gi.max_exchange', 0]]);
		if($post->orderby) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			if(in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
				$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['g.add_time' => SORT_DESC]);
		} else $query->orderBy(['g.add_time' => SORT_DESC]);
		if($post->keyword) {
			$query->andWhere(['like', 'goods_name', $post->keyword]);
		}

		// 指定社区团购商品
		if($post->channel == 'community') {
			if(($childs = GuideshopModel::getCategoryId(true)) !== false && !in_array($post->cate_id, $childs)) {
				$query->andWhere(['in', 'g.cate_id', $childs]);
			}
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			
		$rate = IntegralSettingModel::getSysSetting('rate');
		foreach($list as $key => $value)
		{
			$list[$key]['exchange_rate'] = floatval($rate);
			$list[$key]['exchange_integral'] = floatval($value['max_exchange']);
			$list[$key]['exchange_money'] = round($value['max_exchange'] * $rate, 2);
			$exchange_price = $value['price'] - $list[$key]['exchange_money'];
			if($exchange_price < 0) {
				$list[$key]['exchange_integral'] = round($value['price'] / $rate, 2);
				$list[$key]['exchange_money'] = floatval($value['price']);
				$exchange_price = 0;
			}
			$list[$key]['exchange_price'] = $exchange_price;
			unset($list[$key]['max_exchange']);
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
		}  
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
    }
	
	/**
	 * 当前用户签到领积分
	 * @api 接口访问地址: http://api.xxx.com/integral/signin
	 */
    public function actionSignin()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);
		
		if(!IntegralSettingModel::getSysSetting('enabled')) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('integral_disabled'));
		}
		
		$query = IntegralLogModel::find()->select('userid,add_time')->where(['userid' => Yii::$app->user->id, 'type' => 'signin'])->orderBy(['log_id' => SORT_DESC])->one();
		if($query && Timezone::localDate('Ymd', Timezone::gmtime()) == Timezone::localDate('Ymd', $query->add_time)) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('have_get_integral_for_signin'));
		}

		// 签到领取积分金额
		$signAmount = IntegralSettingModel::getSysSetting('signin');
		if($signAmount <= 0) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('signin_amount_le0'));
		}
		if(($balance = IntegralModel::updateIntegral(['userid' => Yii::$app->user->id, 'type' => 'signin', 'amount' => $signAmount, 'flag' => sprintf(Language::get('signin_integral_flag'), $signAmount)])) === false) {
			return $respond->output(Respond::HANDLE_INVALID, Language::get('signin_integral_fail'));
		}
		$this->params = ['userid' => $query->userid, 'balance' => $balance, 'value' => $signAmount];

		return $respond->output(true, null, $this->params);
    }

	/**
	 * 支持的排序规则
	 */
	private function getOrders()
    {
        return array(
            'add_time'    => Language::get('add_time'),
            'sales'       => Language::get('sales'),
			'price'       => Language::get('price'),
        );
    }
}