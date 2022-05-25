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
	 * @api 接口访问地址: http://api.xxx.com/seller/teambuy/list
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
		
		$query = TeambuyModel::find()->alias('tb')->select('tb.id,tb.specs,tb.status,tb.title,tb.goods_id,tb.people,g.default_image as goods_image,g.price,g.goods_name,g.default_spec as spec_id,g.if_show,g.closed')
			->joinWith('goods g', false, 'INNER JOIN')
			->where(['tb.store_id' => Yii::$app->user->id]);

		if($post->status == 'going') {
			$query->andWhere(['g.if_show' => 1, 'g.closed' => 0, 'tb.status' => 1]);
		}elseif($post->status == 'ended') {
			$query->andWhere(['or', ['tb.status' => 0], ['g.if_show' => 0], ['g.closed' => 1]]);
		}

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($list as $key => $value)
		{
			$list[$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			$list[$key]['teamPrice'] = $this->getTeamPrice($value['spec_id'], $value['specs'], $value['price']);
			unset($list[$key]['specs']);

			if(!$value['status'] || !$value['if_show'] || $value['closed']) {
				$list[$key]['invalid'] = true;
			}
		}

		return $respond->output(true, null, ['list' => $list, 'pagination' => Page::formatPage($page, false)]);
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