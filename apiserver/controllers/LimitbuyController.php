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

use common\models\LimitbuyModel;
use common\models\GoodsSpecModel;
use common\models\GoodsModel;
use common\models\GoodsStatisticsModel;
use common\models\GuideshopModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Promotool;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id LimitbuyController.php 2018.12.8 $
 * @author yxyc
 */

class LimitbuyController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取限时打折列表
	 * @api 接口访问地址: http://api.xxx.com/limitbuy/list
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
		
		$query = LimitbuyModel::find()->alias('lb')->select('lb.id,lb.goods_id,lb.start_time,lb.end_time,lb.image,g.default_image as goods_image,g.price,g.goods_name,g.default_spec as spec_id,s.store_id,s.store_name')
			->joinWith('goods g', false, 'INNER JOIN')
			->joinWith('store s', false)
			->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['<=', 'lb.start_time', Timezone::gmtime()], ['>=', 'lb.end_time', Timezone::gmtime()]]);
		
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

		// 指定社区团购商品
		if($post->channel == 'community') {
			if(($childs = GuideshopModel::getCategoryId(true)) !== false) {
				$query->andWhere(['in', 'g.cate_id', $childs]);
			}
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		$promotool = Promotool::getInstance()->build();
		foreach($list as $key => $value)
		{
			$list[$key]['promotion'] = $promotool->getItemProInfo($value['goods_id'], $value['spec_id']);
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['image'] = Formatter::path($value['image']);
			$list[$key]['sales'] = GoodsStatisticsModel::find()->select('sales')->where(['goods_id' => $value['goods_id']])->scalar();
			$list[$key]['progress'] = LimitBuyModel::getSpeedOfProgress($value['id'], $value['goods_id'], true);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}
	
	/**
	 * 获取限时打折单条信息
	 * @api 接口访问地址: http://api.xxx.com/limitbuy/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['id', 'spec_id', 'goods_id']);

		if($post->id) {
			if(!($post->goods_id = LimitbuyModel::find()->select('goods_id')->where(['id' => $post->id])->scalar())) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_limitbuy'));
			}
		}
		if($post->spec_id) {
			if(!($goods_id = GoodsSpecModel::find()->select('goods_id')->where(['spec_id' => $post->spec_id])->scalar())) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_limitbuy'));
			}
		} elseif($post->goods_id) {
			if(!($post->spec_id = GoodsModel::find()->select('default_spec')->where(['goods_id' => $post->goods_id])->scalar())) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_item'));
			}
		}
		$query = LimitbuyModel::find()->alias('lb')->select('lb.id,lb.title, lb.start_time, lb.end_time, lb.goods_id,lb.image,g.default_image as goods_image,g.price,g.goods_name,s.store_id,s.store_name')
			->joinWith('goods g', false, 'INNER JOIN')->joinWith('store s', false)
			->where(['and', ['s.state' => 1, 'g.if_show' => 1, 'g.closed' => 0], ['lb.goods_id' => $post->goods_id], ['<=', 'lb.start_time', Timezone::gmtime()], ['>=', 'lb.end_time', Timezone::gmtime()]])
			->orderBy(['id' => SORT_DESC]);
		
		// 读取所有规格的价格策略
		if($post->queryrules) {
			$query->addSelect('rules');
		} 

		if(!($record = $query->asArray()->one())) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_limitbuy'));
		}
		$record['start_time'] = Timezone::localDate('Y-m-d H:i:s', $record['start_time']);
		$record['end_time'] = Timezone::localDate('Y-m-d H:i:s', $record['end_time']);
		
		// 只读取默认规格的价格策略
		if(!$post->queryrules) {
			$promotool = Promotool::getInstance()->build();
			$record['promotion'] = $promotool->getItemProInfo($record['goods_id'], $post->spec_id);
		} else {
			$record['rules'] = unserialize($record['rules']);
		}
		$record['goods_image'] = Formatter::path($record['goods_image'], 'goods');
		$record['image'] = Formatter::path($record['image']);
		$record['spec_id'] = $post->spec_id;

		return $respond->output(true, null, $record);
	}
	
	/**
	 * 插入限时打折信息
	 * @api 接口访问地址: http://api.xxx.com/limitbuy/add
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

		$model = new \frontend\models\Seller_limitbuyForm(['store_id' => Yii::$app->user->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
	 * 更新限时打折信息
	 * @api 接口访问地址: http://api.xxx.com/limitbuy/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		$model = new \frontend\models\Seller_limitbuyForm(['id' => $post->id, 'store_id' => Yii::$app->user->id]);
		if(!$model->save($post, true)) {
			return $respond->output(Respond::CURD_FAIL, $model->errors);
		}

		return $respond->output(true);
	}
	
	/**
	 * 删除限时打折信息
	 * @api 接口访问地址: http://api.xxx.com/limitbuy/delete
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

		if(!$post->id || !($model = LimitbuyModel::find()->where(['id' => $post->id, 'store_id' => Yii::$app->user->id])->one()) 
				|| !$model->delete()) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('no_such_limitbuy'));
		}

		return $respond->output(true);
	}

	/**
	 * 支持的排序规则
	 */
	private function getOrders()
    {
        return array(
            'price'    		=> Language::get('price'),
            'end_time'      => Language::get('end_time'),
        );
    }
}