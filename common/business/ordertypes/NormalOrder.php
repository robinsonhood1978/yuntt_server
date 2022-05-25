<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\business\ordertypes;

use yii;
use yii\helpers\Json;

use common\models\CartModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Promotool;
use common\library\Page;

use common\business\BaseOrder;

/**
 * @Id NormalOrder.php 2018.7.12 $
 * @author mosir
 */

class NormalOrder extends BaseOrder
{
	protected $otype = 'normal';

	/**
	 * 显示订单表单
	 * @api API接口使用到该数据 
	 */
	public function formData(&$goods_info = array())
	{
		$result = array();

		// 获取我的收货地址
		$result['my_address'] = $this->getMyAddress();

		// 配送方式
		$shipping_method = $this->getOrderShippings($goods_info);
		$result['shipping_methods'] = $shipping_method;

		// API接口数据，把收货地址的省市区格式一下
		if(Basewind::getCurrentApp() == 'api' && $result['my_address']) {
			foreach($result['my_address'] as $key => $value) {
				$result['my_address'][$key] = array_merge($value, RegionModel::getArrayRegion(0, $value['region_name']));
				unset($result['my_address'][$key]['region_name']);
			}
		}

		// API接口不需要这些数据
		if (Basewind::getCurrentApp() != 'api') {
			$result['addresses'] = Json::encode($result['my_address']);

			// 获取一级地区（用于多级地区选择）
			$result['regions'] = RegionModel::find()->select('region_id,region_name')->where(['parent_id' => 0])->indexBy('region_id')->column();

			if (!($shipping_method = $this->getOrderShippings($goods_info))) {
				$this->errors = Language::get('no_shipping_methods');
				return false;
			}
	
			// API接口不需要格式化这些数据
			$result['shippings'] = Json::encode($result['shipping_methods']);

			// 取默认（第一条地区对应的运费），此作用为：取每个店铺的第一个收货地址各个运送方式的资费，以便第一次加载时显示
			foreach ($shipping_method as $key => $value) {
				$result['shipping_methods'][$key] = current($value);
			}
		}
		// 获取店铺可用优惠券
		$goods_info = $this->getStoreCouponList($goods_info);

		// 获取订单提交页面显示该订单所有营销工具信息
		foreach ($goods_info['storeIds'] as $store_id) {
			Promotool::getInstance()->build(['store_id' => $store_id])->getOrderAllPromotoolInfo($goods_info);
		}

		return $result;
	}

	/** 
	 * 提交生成的订单
	 * @return array $result 包含店铺和订单号的数组
	 */
	public function submit($data = array())
	{
		extract($data);

		// 处理订单基本信息
		if (!($base_info = parent::handleOrderInfo($goods_info))) {
			return false;
		}

		// 处理订单收货人信息
		if (!($consignee_info = $this->handleConsigneeInfo($goods_info))) {
			return false;
		}

		// 获取订单折扣信息
		if (($discount_info = $this->getAllDiscountByPost($goods_info)) === false) {
			return false;
		}

		// 检验折扣信息和订单总价的合理性
		if (!isset($goods_info['integralExchange']['rate'])) $goods_info['integralExchange']['rate'] = 0;
		if (!$this->checkAllDiscountForOrderAmount($base_info, $discount_info, $consignee_info, $goods_info['integralExchange']['rate'])) {
			return false;
		}

		// 至此说明订单的信息都是可靠的，可以开始入库了
		if(($result = parent::insertOrderData($base_info, $goods_info, $consignee_info)) === false) {
			return false;
		}

		// 更新优惠券的使用次数
		if (isset($discount_info['coupon'])) {
			parent::updateCouponRemainTimes($result, $discount_info['coupon']);
		}

		// 保存每个订单使用的积分数额（处理合并付款订单的积分分摊）
		if (isset($discount_info['integral'])) {
			parent::saveIntegralInfoByOrder($result, $discount_info['integral']);
		}

		return $result;
	}

	/**
	 * 获取购物车中的商品数据
	 * 用来计算订单可使用的最大积分值等
	 */
	public function getOrderGoodsList()
	{
		$query = CartModel::find()->alias('c')->select('gs.spec_id,gs.spec_1,gs.spec_2,gs.stock,c.rec_id,c.userid,c.store_id,c.goods_id,c.goods_name,c.goods_image,c.specification,c.price,c.quantity,c.product_id,c.selected')->joinWith('goodsSpec gs', false)
			->where(['userid' => Yii::$app->user->id, 'selected' => 1])->indexBy('product_id');
		if ($this->post->store_id) {
			$query->andWhere(['store_id' => $this->post->store_id]);
		}

		$result = $query->asArray()->all();
		foreach ($result as $key => $value) {
			$result[$key]['goods_image'] = Page::urlFormat($value['goods_image'], Yii::$app->params['default_goods_image']);

			// 如果规格已经不存在，则删除购物车该规格商品记录
			if(!$value['spec_id']) {
				unset($result[$key]);
				CartModel::deleteAll(['rec_id' => $value['rec_id']]);
			}
		}

		$list = CartModel::reBuildByQuantity(['items' => $result]);
		$result = $list['items'];

		return array($result, null);
	}

	/**
	 * 通过收货人或地区ID获取地址信息
	 * @param int $addr_id 针对发货订单
	 * @param int $region_id 针对社区团购，买家自提模式
	 */
	public function getAddressInfo($addr_id = 0, $region_id = 0) {
		return parent::getMyAddress($addr_id);
	}

	/**
	 * 获取我的所有配送目的地列表，用于计算每个地址的运费
	 * 对于普通订单，配送目的地为收货地址
	 */
	public function getDeliveryTos()
	{
		$list = parent::getMyAddress();
		if(empty($list)) {
			return array();
		}

		$result = [];
		foreach($list as $key => $value) {
			$result[] = $value['region_id'];
		}
		
		return array_values(array_unique($result));
	}
}
