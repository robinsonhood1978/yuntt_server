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

use common\models\GoodsQaModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_qaForm.php 2018.10.19 $
 * @author mosir
 */
class My_qaForm extends Model
{
	public $store_id = null;
	public $errors = null;
	
	public function formData($post = null, $pageper = 10)
	{
		$query = GoodsQaModel::find()->alias('qa')->select('qa.ques_id,qa.question_content,qa.reply_content,qa.time_post,qa.time_reply,qa.userid,qa.item_name,qa.item_id,qa.type,qa.if_new,u.username,u.portrait,s.store_name,s.store_logo')->joinWith('user u', false)->joinWith('store s', false)->where(['qa.store_id' => Yii::$app->user->id])->orderBy(['if_new' => SORT_DESC, 'time_post' => SORT_DESC])->indexBy('ques_id');

		if($post->type == 'reply') {
			$query->andWhere(['reply_content' => '']);
		}
		if($post->type == 'replied') {
			$query->andWhere(['>', 'reply_content', '']);
		}
		
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		if(Basewind::getCurrentApp() == 'wap') 
		{
			foreach($recordlist as $key => $record)
			{
				$recordlist[$key]['time_post'] = Timezone::localDate('Y-m-d', $record['time_post']);
				$recordlist[$key]['time_reply'] = Timezone::localDate('Y-m-d', $record['time_reply']);
				
				if(empty($record['store_logo'])) $recordlist[$key]['store_logo'] = Yii::$app->params['default_store_logo']; 
				if(empty($record['portrait'])) $recordlist[$key]['portrait'] = Yii::$app->params['default_user_portrait']; 
			}
		}
		
		if ($post->type == 'reply') {
			GoodsQaModel::updateAll(['if_new' => 0], ['in', 'ques_id', array_keys($recordlist)]);
        }
		
		return array($recordlist, $page);
	}
}
