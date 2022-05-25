<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model;

use common\models\CartModel;
use common\models\StoreModel;
use common\models\GoodsSpecModel;

use common\library\Promotool;
use common\library\Language;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id CartForm.php 2018.11.10 $
 * @author yxyc
 */
class CartForm extends \frontend\models\CartForm
{
	public $errors = null;
	
	// 记录API错误代码
	public $code;
	
	public function valid($post, $extra = [])
	{
		if(!$post->spec_id) {
			$this->code = Respond::PARAMS_INVALID;
			$this->errors = Language::get('select_specs');
			return false;
		}
		if(!$post->quantity) {
			$this->code = Respond::PARAMS_INVALID;
			$this->errors = Language::get('quantity_invalid');
			return false;
		}
		
        // 是否有商品
		if(!($specInfo = GoodsSpecModel::find()->alias('gs')->select('g.store_id, g.goods_id, g.goods_name, g.spec_name_1, g.spec_name_2, g.default_image, gs.spec_id, gs.spec_1, gs.spec_2, gs.stock, gs.price, gs.spec_image')->joinWith('goods g', false)->where(['spec_id' => $post->spec_id])->asArray()->one())) {
			$this->code = Respond::RECORD_NOTEXIST;
			$this->errors = Language::get('no_such_goods');
			return false;
		}
		
		// 如果是自己店铺的商品，则不能购买
		if($specInfo['store_id'] == Yii::$app->user->id) {
			$this->code = Respond::HANDLE_INVALID;
			$this->errors = Language::get('can_not_buy_yourself');
			return false;
		}
		if($specInfo['stock'] < $post->quantity) {
			$this->code = Respond::PARAMS_INVALID;
			$this->errors = Language::get('no_enough_goods');
			return false;
		}

		// 读取促销价格
		$promotool = Promotool::getInstance()->build();
		if(($result = $promotool->getItemProInfo($specInfo['goods_id'], $post->spec_id, $extra)) !== false) {
			if($result['price'] != $specInfo['price']) {
				$specInfo['price'] = $result['price'];
			}
		}

		return array_merge($specInfo, ['quantity' => $post->quantity]);
    }

	/**
	 * 返回购物车数据
	 */
	public function getCart()
	{
		$list = parent::getCart();

		if(!empty($list['items'])) {
			foreach($list['items'] as $key => $value) {
				$list['items'][$key]['goods_image'] = Formatter::path($value['goods_image'], 'goods');
			}
		}

		return $list;
	}
}
