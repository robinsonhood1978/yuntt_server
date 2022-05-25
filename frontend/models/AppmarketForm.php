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

use common\models\AppmarketModel;
use common\models\ApprenewalModel;

use common\library\Language;
use common\library\Page;

/**
 * @Id AppmarketForm.php 2018.10.11 $
 * @author mosir
 */
class AppmarketForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		$query = AppmarketModel::find()->select('aid,title,summary,category,price,logo,views,sales,appid,status')->where(['status' => 1]);
		if(in_array($post->orderby, ['sales|desc', 'views|desc', 'add_time|desc'])) {
			$orderBy = explode('|', $post->orderby);
			$query->orderBy([$orderBy[0] => ($orderBy[1] == 'asc') ? SORT_ASC : SORT_DESC]);
		} else $query->orderBy(['add_time' => SORT_DESC]);
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		foreach($recordlist as $key => $record)
		{
			if(!$record['logo']) $recordlist[$key]['logo'] = Yii::$app->params['default_goods_image'];
			
			if(ApprenewalModel::checkIsRenewal($record['appid'], Yii::$app->user->id)){
				$recordlist[$key]['checkIsRenewal'] = true;
			}
			
			// for WAP Only
			$recordlist[$key]['name'] = Language::get($record['appid']);
			
		}
		return array($recordlist, $page);
	}
}
