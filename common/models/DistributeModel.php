<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\models;

use Yii;
use yii\db\ActiveRecord;

use common\models\UserModel;
use common\models\OrderModel;

/**
 * @Id DistributeModel.php 2018.10.22 $
 * @author mosir
 */

class DistributeModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%distribute}}';
    }
	
	// 关联表
	public function getUser()
	{
		return parent::hasOne(UserModel::className(), ['userid' => 'userid']);
	}
	
	/**
	 * 订单完成后分销返现。该操作可以不受是否开启三级分销的影响 
	 * 运费金额不参与返佣
	 */
	public static function distributeInvite($order = array())
	{
		// 转到对应的业务实例，不同的业务实例用不同的文件处理，如购物，卖出商品，充值，提现等，每个业务实例又继承支出或者收入 
		$depopay_type    = \common\library\Business::getInstance('depopay')->build('distribute');
		$result = $depopay_type->distribute($order);
		
		if($result !== true) {
			return false;
		}
		return true;
	}
	
	/**
	 * 获取可用于返佣的金额基数（如有退款，退款金额不参与返佣）
	 * 支付金额-运费金额-退款金额（不含退运费金额）
	 */
	public static function getDistributeAmount($order = array())
	{
		list($realGoodsAmount, $realShippingFee, $realOrderAmount) = OrderModel::getRealAmount($order['order_id']);
		
		// 如果有退款，减掉退掉的商品金额
		$query = RefundModel::find()->alias('r')->select('r.refund_goods_fee')->joinWith('depositTrade dt', false)->where(['r.buyer_id' => $order['buyer_id'], 'dt.bizOrderId' => $order['order_sn'], 'r.status' => 'SUCCES'])->one();
		if($query && $query->refund_goods_fee > 0) {
			$realGoodsAmount = $realGoodsAmount - $query->refund_goods_fee;
		}
		return $realGoodsAmount > 0 ? $realGoodsAmount : 0;
	}
	
	/**
	 * 获取每个商品所占整个订单总额的比例 
	 * 当下单后，修改过价格，或折扣优惠后，实际付款的金额并非等于订单中每个商品原单价之和的时候必须考虑分摊总金额，要不会出现超分（佣）的情况
	 */
	public static function getItemRate($items = array(), $distributeAmount)
	{
		$total = 0;
		$each = array();
		foreach($items as $key => $item) {
			$total += $item->price * $item->quantity;
		}
		
		if($total <= 0) {
			
			// 如果订单商品表的商品总和为零，但是实际支付金额不为零（此种情况主要是改价导致，那么每个商品分摊比例相等
			if($distributeAmount > 0) {
				foreach($items as $key => $item) {
					$each[$key] = 1/count($items);
				}
				return array($each, $total);
			}
			return array(false, $total);
		}
	
		foreach($items as $key => $item) {
			$each[$key] = ($item->price * $item->quantity) / $total;
		}
		
		return array($each, $total);
	}

	/**
	 * 获取商品/店铺等的分销邀请码
	 */
	public static function getInviteCode($params = [])
	{
		// CODE太长会导致小程序中无法生成小程序码
		return base64_encode(implode('-', [$params['type'], $params['id'], $params['uid']]));
	}
}