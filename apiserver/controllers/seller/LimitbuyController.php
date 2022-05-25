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

use common\models\LimitbuyModel;

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
	 * @api 接口访问地址: http://api.xxx.com/seller/limitbuy/list
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
		
		$query = LimitbuyModel::find()->alias('lb')->select('lb.id,lb.goods_id,lb.start_time,lb.end_time,lb.image,g.default_image as goods_image,g.price,g.goods_name,g.default_spec as spec_id,g.if_show,g.closed')
			->joinWith('goods g', false, 'INNER JOIN')->where(['lb.store_id' => Yii::$app->user->id]);
		
		if($post->status == 'going') {
			$query->andWhere(['and', ['g.if_show' => 1, 'g.closed' => 0], ['<', 'lb.start_time', Timezone::gmtime()], ['>', 'lb.end_time', Timezone::gmtime()]]);
		}elseif($post->status == 'ended') {
			$query->andWhere(['or', ['g.if_show' => 0], ['g.closed' => 1], ['<', 'lb.end_time', Timezone::gmtime()]]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value)
		{
			if($promotion = LimitbuyModel::getItemProPrice($value['goods_id'], $value['spec_id'])) {
				$list[$key]['promotion']['price'] = $promotion[0];
			}

			if(!$value['if_show'] || $value['closed'] || ($value['end_time'] < Timezone::gmtime())) {
				$list[$key]['invalid'] = true;
			}

			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['image'] = Formatter::path($value['image']);
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
	}
}