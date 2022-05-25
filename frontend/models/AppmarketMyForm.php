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

use common\models\ApprenewalModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id AppmarketMyForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketMyForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		$query = ApprenewalModel::find()->alias('ar')->select('ar.*,a.aid,a.title,a.summary,a.category,a.logo')->joinWith('appmarket a', false)->where(['userid' => Yii::$app->user->id])->orderBy(['rid' => SORT_DESC]);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($recordlist as $key => $record)
		{
			// 如果没到期，则获取剩余时间文本及数组
			if($timediff = $this->getExpiredDetail($record['expired'])) {
				$recordlist[$key]['timediff'] = $timediff;
				$recordlist[$key]['checkIsRenewal'] = true; 
			}
			!$record['logo'] && $recordlist[$key]['logo'] = Yii::$app->params['default_goods_image'];	
			
			if(Basewind::getCurrentApp() == 'wap') {
				$recordlist[$key]['name'] = Language::get($record['appid']);
				$recordlist[$key]['expired'] = Timezone::localDate('Y-m-d', $record['expired']);
			}
		}
		return array($recordlist, $page);
	}
	
	private function getExpiredDetail($expired = 0)
	{
		$timediff = array();
		
		if($expired > Timezone::gmtime())
		{
			$timediff = Timezone::lefttime($expired);
			if($timediff['d'] < 1) {
				$text = sprintf(Language::get('timediff_format_hour'), $timediff['h'], $timediff['m']);
			}elseif($timediff['d'] < 7) {
				$text = sprintf(Language::get('timediff_format_week'), $timediff['d'], $timediff['h'], $timediff['m']);
			}elseif($timediff['d'] < 1115) {
				$text = sprintf(Language::get('timediff_format_day'), $timediff['d'], $timediff['h'], $timediff['m']);
			}
			
			$text && $timediff['format'] = $text;	
		}
		return $timediff;
	}
}
