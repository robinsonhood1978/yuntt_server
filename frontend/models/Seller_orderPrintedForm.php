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
use common\library\Language;

/**
 * @Id Seller_orderPrintedForm.php 2018.9.19 $
 * @author mosir
 */
class Seller_orderPrintedForm extends Model
{
	public $store_id = 0;
	public $errors = null;
	
	public function formData($post = null)
	{
		if(!$post->order_id || !($orders = OrderModel::find()->alias('o')->select('o.order_id,order_sn,status,buyer_id,buyer_name,seller_name,order_amount,shipping_fee,region_name, address,postscript,o.add_time,pay_time,ship_time,finished_time,consignee,ex.phone_mob,ex.phone_tel,payment_name,shipping_name,u.im_qq')->joinWith('orderExtm ex', false)->joinWith('orderBuyerInfo u', false)->with('orderGoods')->where(['in', 'o.order_id', explode(',', $post->order_id)])->andWhere(['seller_id' => $this->store_id])->indexBy('order_id')->orderBy(['o.order_id' => SORT_DESC])->asArray()->all())) {
			$this->errors = Language::get('no_such_order');
			return false;
		}
		return $orders;
	}
}
