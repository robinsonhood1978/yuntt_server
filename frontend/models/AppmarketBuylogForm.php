<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 
use yii\helpers\Url;

use common\models\AppbuylogModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Language;
use common\library\Page;
use common\library\Def;

/**
 * @Id AppmarketBuylogForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketBuylogForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		$query = AppbuylogModel::find()->alias('ab')->select('ab.*,a.aid,a.title,a.summary,a.category,a.logo')->joinWith('appmarket a', false)->where(['userid' => Yii::$app->user->id])->orderBy(['bid' => SORT_DESC]);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($recordlist as $key => $record) {
			!$record['logo'] && $recordlist[$key]['logo'] = Yii::$app->params['default_goods_image'];	
			
			$recordlist[$key]['status_label'] = Def::getOrderStatus($record['status']);
			if($record['status'] == Def::ORDER_PENDING) {
				$recordlist[$key]['buyUrl'] = Url::toRoute(['appmarket/cashier', 'id' => $record['bid']]);
			}
			
			if(Basewind::getCurrentApp() == 'wap') {
				$recordlist[$key]['name'] = Language::get($record['appid']);
				$recordlist[$key]['add_time'] = Timezone::localDate('Y-m-d', $record['add_time']);
			}
		}
		return array($recordlist, $page);
	}
}
