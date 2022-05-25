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
use yii\helpers\ArrayHelper;

use common\models\OrderModel;
use common\models\RefundModel;
use common\models\DepositTradeModel;

use common\library\Basewind;
use common\library\Timezone;
use common\library\Page;
use common\library\Def;

/**
 * @Id Seller_orderForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null, $pageper = 10) 
	{
		$query = OrderModel::find()->alias('o')->select('o.*, ex.shipping_fee')->where(['seller_id' => $this->store_id])->orderBy(['o.order_id' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), $pageper);
		$orders = $query->joinWith('orderExtm ex', false)->with('orderGoods')->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($orders as $key => $order)
        {
			if(!$order['orderGoods']) {
				unset($orders[$key]);
				continue;
			}
            foreach($order['orderGoods'] as $k => $goods)
            {
                if(empty($goods['goods_image'])) {
					$orders[$key]['orderGoods'][$k]['goods_image'] = Yii::$app->params['default_goods_image'];
				}
            }
			// for WAP
			if(Basewind::getCurrentApp() == 'wap') {
				$orders[$key]['status_label'] = Def::getOrderStatus($order['status']);
			}
				
			// 是否申请过退款
			$tradeNo = DepositTradeModel::find()->select('tradeNo')->where(['bizIdentity' => Def::TRADE_ORDER, 'bizOrderId' => $order['order_sn']])->scalar();
			
			if(!empty($tradeNo) && ($refund = RefundModel::find()->select('refund_id,status')->where(['tradeNo' => $tradeNo])->one())) {
				$orders[$key]['refund_status'] = $refund->status;
				$orders[$key]['refund_id'] = $refund->refund_id;
			}
			$orders[$key]['goods_quantities'] = count($order['orderGoods']);
        }
		
		return array($orders, $page);
	}
	
	public function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['type', 'buyer_name', 'add_time_from', 'add_time_to', 'order_sn'])) {
					return true;
				}
			}
			return false;
		}
		if(isset($post->type) && ($status = Def::getOrderStatusTranslator($post->type)) > -1) {
			$query->andWhere(['o.status' => $status]);
		}
		if($post->buyer_name) {
			$query->andWhere(['like', 'buyer_name', $post->buyer_name]);
		}
		if($post->add_time_from) {
			$query->andWhere(['>=', 'add_time', Timezone::gmstr2time($post->add_time_from)]);
		}
		if($post->add_time_to) {
			$query->andWhere(['<=', 'add_time', Timezone::gmstr2time_end($post->add_time_to)]);
		}
		if($post->order_sn) {
			$query->andWhere(['order_sn' => $post->order_sn]);
		}
		
		return $query;
	}
}
