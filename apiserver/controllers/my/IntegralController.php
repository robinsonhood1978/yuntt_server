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

use common\models\GoodsModel;
use common\models\OrderModel;
use common\models\IntegralModel;
use common\models\IntegralSettingModel;
use common\models\IntegralLogModel;

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
	 * 获取当前用户积分记录列表
	 * @api 接口访问地址: http://api.xxx.com/my/integral/list
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
		
		$query = IntegralLogModel::find()->select('log_id,userid,changes,state,order_id,order_sn,balance,type,flag,add_time')->where(['userid' => Yii::$app->user->id])->orderBy(['log_id' => SORT_DESC]);
		if(in_array($post->type, ['income'])) {
			$query->andWhere(['>', 'changes', 0])->andWhere(['state' => 'finished']);
		} elseif(in_array($post->type, ['pay'])) {
			$query->andWhere(['<', 'changes', 0])->andWhere(['state' => 'finished']);
		} elseif(in_array($post->type, ['frozen'])) {
			$query->andWhere(['state' => 'frozen']);
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value)
		{
			$list[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $value['add_time']);
			//$list[$key]['state'] = IntegralModel::getStatusLabel($value['state']);
			$list[$key]['name'] = Language::get($value['type']);
			unset($list[$key]['type']);
			if($value['order_id']) {
				$list[$key]['order_sn'] = OrderModel::find()->select('order_sn')->where(['order_id' => $value['order_id']])->scalar();
			}
		}
		
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, null, $this->params);
    }
}