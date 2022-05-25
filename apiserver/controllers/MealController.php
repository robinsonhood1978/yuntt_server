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

use common\models\MealModel;
use common\models\GoodsModel;
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
 * @Id MealController.php 2018.12.28 $
 * @author yxyc
 */

class MealController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取搭配购列表
	 * @api 接口访问地址: http://api.xxx.com/meal/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['store_id', 'goods_id', 'page', 'page_size']);

		$orderBy = [];
		if($post->orderby) {
			$orderBy = Basewind::trimAll(explode('|', $post->orderby));
			if(in_array($orderBy[0], array_keys($this->getOrders())) && in_array(strtolower($orderBy[1]), ['desc', 'asc'])) {
				$orderBy = [$orderBy[0] => strtolower($orderBy[1]) == 'asc' ? SORT_ASC : SORT_DESC];
			} 
		}

		$model = new \frontend\models\MealForm();
		list($list, $page) = $model->formData($post, isset($post->queryitem) ? (bool)$post->queryitem : true, $orderBy, true, $post->page_size, false, $post->page);
		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}
	
	/**
	 * 获取搭配购单条信息
	 * @api 接口访问地址: http://api.xxx.com/meal/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['meal_id']);

		if(!$post->meal_id || !MealModel::find()->where(['meal_id' => $post->meal_id])->exists()) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('on_sush_item'));
		}

		$model = new \frontend\models\MealForm(['id' => $post->meal_id]);
		list($list) = $model->formData($post, isset($post->queryitem) ? (bool)$post->queryitem : true);
		return $respond->output(true, null, $list ? current($list) : null);
	}
	
	/**
	 * 插入搭配购信息
	 * @api 接口访问地址: http://api.xxx.com/meal/add
	 */
    public function actionAdd()
    {
		
	}
	
	/**
	 * 更新搭配购信息
	 * @api 接口访问地址: http://api.xxx.com/meal/update
	 */
    public function actionUpdate()
    {
		
	}
	
	/**
	 * 删除搭配购信息
	 * @api 接口访问地址: http://api.xxx.com/meal/delete
	 */
    public function actionDelete()
    {
		
	}

	/**
	 * 支持的排序规则
	 */
	private function getOrders()
    {
        return array(
            'price'    		=> Language::get('price'),
            'created'      	=> Language::get('add_time'),
        );
    }
}