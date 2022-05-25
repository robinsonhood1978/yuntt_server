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

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\RefundMessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;
use common\library\Def;

/**
 * @Id RefundViewForm.php 2018.10.17 $
 * @author mosir
 */
class RefundViewForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4)
	{
		if(!$post->id || !($refund = RefundModel::find()->alias('r')->select('r.*,dt.bizOrderId,dt.bizIdentity')->joinWith('depositTrade dt', false)->where(['refund_id' => $post->id])->andWhere(['or', ['r.buyer_id' => Yii::$app->user->id], ['r.seller_id' => Yii::$app->user->id]])->asArray()->one())) {
			$this->errors = Language::get('no_such_refund');
			return false;
		}
		$refund['shipped_label'] = Language::get('shipped_'.$refund['shipped']);
		$refund['status_label'] = Language::get('REFUND_'.strtoupper($refund['status']));
		
		// for WAP Only
		if(($refund['bizIdentity'] == Def::TRADE_ORDER) && $refund['bizOrderId']) {
			if(($order = OrderModel::find()->select('order_id')->where(['order_sn' => $refund['bizOrderId']])->one())) {
				$refund['order_id'] = $order->order_id;
			}
		}

		if(Basewind::getCurrentApp() == 'pc') {
			$query = RefundMessageModel::find()->where(['refund_id' => $post->id])->orderBy(['created' => SORT_DESC]);
			$page = Page::getPage($query->count(), $pageper);
			$refund['messagelist'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		}
		
		return array($refund, $page);
	}
}