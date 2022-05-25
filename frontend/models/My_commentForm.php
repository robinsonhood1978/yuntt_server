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

use common\models\UserModel;
use common\models\OrderGoodsModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_commentForm.php 2018.10.21 $
 * @author mosir
 */
class My_commentForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null, $pageper = 10)
	{
		$query = OrderGoodsModel::find()->alias('og')->select('og.rec_id,og.goods_id,og.goods_name,og.comment, og.reply_comment,og.reply_time,o.order_id,o.buyer_id,o.buyer_name,o.seller_id,o.seller_name,o.evaluation_time')->joinWith('order o', false)->where(['o.seller_id' => $this->store_id])->orderBy(['evaluation_time' => SORT_DESC]);
			
		if(in_array($post->type, ['reply'])) {
			$query->andWhere(['=', 'reply_comment', '']);
		}
		if(in_array($post->type, ['replied'])) {
			$query->andWhere(['!=', 'reply_comment', '']);
		}
			
		$page = Page::getPage($query->count(), $post->pageper);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		if(Basewind::getCurrentApp() == 'wap')
		{
			foreach($list as $key => $val) {
				if($val['evaluation_time']) $list[$key]['evaluation_time'] = Timezone::localDate('Y-m-d H:i:s', $val['evaluation_time']);
				if($val['reply_time']) $list[$key]['reply_time'] = Timezone::localDate('Y-m-d H:i:s', $val['reply_time']);
				
				if(($portrait = UserModel::find()->select('portrait')->where(['userid' => $val['buyer_id']])->scalar())) {
					$list[$key]['buyer_portrait'] = $portrait;
				} else $list[$key]['buyer_portrait'] = Yii::$app->params['default_user_portrait'];
				
				if(($portrait = UserModel::find()->select('portrait')->where(['userid' => $val['seller_id']])->scalar())) {
					$list[$key]['seller_portrait'] = $portrait;
				} else $list[$key]['seller_portrait'] = Yii::$app->params['default_user_portrait'];
			}
		}
		return array($list, $page);
	}
}
