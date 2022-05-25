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

use common\models\CashcardModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_cashcardForm.php 2018.10.3 $
 * @author mosir
 */
class My_cashcardForm extends Model
{
	public $errors = null;

	public function formData($post = null, $pageper = 4, $isAJax = false, $curPage = false) 
	{
		$query = CashcardModel::find()->alias('c')->select('c.cardNo,c.id,c.name,c.money,c.add_time,c.active_time,c.expire_time, dt.tradeNo')->joinWith('depositTrade dt', false)->where(['useId' => Yii::$app->user->id])->orderBy(['id' => SORT_DESC]);
	
		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $record)
		{
			$recordlist[$key]['valid'] = 1;
			
			if($record['expire_time'] > 0) {
				if(Timezone::gmtime() > $record['expire_time']) {
					$recordlist[$key]['valid'] = 0;
				}
			}
			 
			if(in_array(Basewind::getCurrentApp(), ['api', 'wap'])) {
				$recordlist[$key]['add_time'] = Timezone::localDate('Y-m-d H:i:s', $record['add_time']);
				$record['active_time'] > 0 && $recordlist[$key]['active_time'] = Timezone::localDate('Y-m-d H:i:s', $record['active_time']);
				$record['expire_time'] > 0 && $recordlist[$key]['expire_time'] = Timezone::localDate('Y-m-d H:i:s', $record['expire_time']);
			}
		}
		return array($recordlist, $page);
	}
}
