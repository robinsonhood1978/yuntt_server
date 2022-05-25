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

use common\models\TeambuyModel;
use common\models\TeambuyLogModel;
use common\models\GoodsSpecModel;
use common\models\GoodsStatisticsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id TeambuyController.php 2019.12.8 $
 * @author yxyc
 */

class TeambuyController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取拼团活动列表
	 * @api 接口访问地址: http://api.xxx.com/teambuy/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'status', 'page', 'page_size']);
		
		$query = TeambuyModel::find()->alias('tb')->select('tb.id,tb.specs,tb.status,tb.title,tb.goods_id,tb.people,g.default_image as goods_image,g.price,g.goods_name,g.default_spec as spec_id,s.store_id,s.store_name,gst.sales')
			->joinWith('goods g', false, 'INNER JOIN')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)
			->where(['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0]);
			
		if(isset($post->store_id)) {
			$query->andWhere(['s.store_id' => $post->store_id]);
		}
		if(isset($post->items) && !empty($post->items)) {
			$query->andWhere(['in', 'g.goods_id', explode(',', $post->items)]);
		}
		if($post->orderby) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			if(in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
				$query->orderBy([$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC]);
			} else $query->orderBy(['id' => SORT_DESC]);
		} else $query->orderBy(['id' => SORT_DESC]);
		if($post->keyword) {
			$query->andWhere(['or', ['like', 'title', $post->keyword], ['like', 'goods_name', $post->keyword]]);
		}
		if(isset($post->status)) {
			$query->andWhere(['tb.status' => $post->status]);
		}
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($list as $key => $value)
		{
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['teamPrice'] = $this->getTeamPrice($value['spec_id'], $value['specs'], $value['price']);
			unset($list[$key]['specs']);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}
	
	/**
	 * 获取拼团活动单条信息
	 * @api 接口访问地址: http://api.xxx.com/teambuy/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'spec_id', 'status']);

		if($post->spec_id) {
			if(!($spec = GoodsSpecModel::find()->select('goods_id,price')->where(['spec_id' => $post->spec_id])->one())) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_teambuy'));
			}
			$post->goods_id = $spec->goods_id;
		} 
		elseif($post->id) {
			if(!($teambuy = TeambuyModel::find()->select('goods_id')->where(['id' => $post->id])->one())) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_teambuy'));
			}
			$post->goods_id = $teambuy->goods_id;
		}

		$query = TeambuyModel::find()->alias('tb')->select('tb.id,tb.specs,tb.status,tb.title,tb.people,tb.goods_id,g.default_image as goods_image,g.goods_name,s.store_id,s.store_name,gst.sales')
			->joinWith('goods g', false, 'INNER JOIN')->joinWith('store s', false)->joinWith('goodsStatistics gst', false)
			->where(['tb.goods_id' => $post->goods_id, 's.state' => 1, 'g.if_show' => 1, 'g.closed' => 0])
			->orderBy(['id' => SORT_DESC]);
		if(isset($post->status)) {
			$query->andWhere(['status' => $post->status]);
		}
		if(!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_teambuy'));
		}

		// 传规格ID才返回该规格对应的价格
		if($post->spec_id) {
			$record['price'] = $this->getTeamPrice($post->spec_id, $record['specs'], $spec->price);
			$record['spec_id'] = $post->spec_id;
		}

		// 处理其他规格的价格
		if(($specs = unserialize($record['specs']))) {
			$all = [];
			foreach($specs as $key => $value) {
				$all[$key] = ['price' => $this->getTeamPrice($key, $specs), 'discount' => $value['price']];
			}
			$record['specs'] = $all;
		}
		$record['goods_image'] = Formatter::path($record['goods_image'], 'goods');

		return $respond->output(true, null, $record);
	}

	/**
	 * 获取拼团订单列表信息
	 * @api 接口访问地址: http://api.xxx.com/teambuy/logs
	 */
    public function actionLogs()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'tbid','teamid', 'page', 'page_size']);
		
		$query = TeambuyLogModel::find()->alias('tl')->select('tl.*, u.username,u.nickname,u.portrait,g.goods_name,g.default_image as goods_image')->joinWith('user u', false)->joinWith('goods g', false)->where(['>', 'expired', Timezone::gmtime()])->orderBy(['expired' => SORT_ASC]);
		if(isset($post->ispayed)) {
			intval($post->ispayed) ? $query->andWhere(['>', 'pay_time', 0]) : $query->andWhere(['pay_time' => 0]);
		}
		if($post->goods_id) {
			$query->andWhere(['g.goods_id' => $post->goods_id]);
		}
		if($post->tbid) {
			$query->andWhere(['tbid' => $post->tbid]);
		}
		if($post->teamid) {
			$query->andWhere(['teamid' => $post->teamid]);
		}
		if(isset($post->status)) {
			$query->andWhere(['status' => intval($post->status)]);
		}
		if($post->keyword) {
			$query->andWhere(['like', 'g.goods_name', $post->keyword]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['timestamp'] = $value['expired'] - Timezone::gmtime();
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['portrait'] = Formatter::path($value['portrait'], 'user');
			$list[$key]['surplus'] = $value['people'] - TeambuyLogModel::find()->select('logid')->where(['and', ['teamid' => $value['teamid'], 'status' => 0], ['>', 'pay_time', 0]])->count();
			foreach($value as $k => $v) {
				if(in_array($k, ['created', 'expired', 'pay_time'])) {
					$list[$key][$k] = $v ? Timezone::localDate('Y-m-d H:i:s', $v) : 0;
				}
			}
		}

		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
	}
		
	/**
	 * 插入拼团信息
	 * @api 接口访问地址: http://api.xxx.com/teambuy/add
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

		$model = new \frontend\models\TeambuyForm(['store_id' => Yii::$app->user->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
	 * 更新拼团信息
	 * @api 接口访问地址: http://api.xxx.com/teambuy/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		$model = new \frontend\models\TeambuyForm(['store_id' => Yii::$app->user->id, 'id' => $post->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
	 * 删除拼团活动
	 * @api 接口访问地址: http://api.xxx.com/teambuy/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		if(!TeambuyModel::deleteAll(['id' => $post->id, 'store_id' => Yii::$app->user->id])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('drop_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 关闭拼团活动
	 * @api 接口访问地址: http://api.xxx.com/teambuy/closed
	 */
    public function actionClosed()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id']);

		if(!TeambuyModel::updateAll(['status' => 0], ['id' => $post->id, 'store_id' => Yii::$app->user->id])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('handle_fail'));
		}

		return $respond->output(true);
	}

	/**
	 * 支持的排序规则
	 */
	private function getOrders()
    {
        return array(
            'price'	=> Language::get('price'),
            'sales'	=> Language::get('sales'),
        );
    }

	/**
	 * 计算拼团价格
	 */
	private function getTeamPrice($spec_id, $specs = array(), $price = 0) {
		if(!is_array($specs)) {
			$specs = unserialize($specs);
		}

		if(!$price) {
			$price = GoodsSpecModel::find()->select('price')->where(['spec_id' => $spec_id])->scalar();
		}
		if(!isset($specs[$spec_id])) {
			return $price;
		}
		return round($price * $specs[$spec_id]['price'] / 1000, 4) * 100;
	}
}