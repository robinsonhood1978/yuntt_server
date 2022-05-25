<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\WholesaleModel;
use common\models\GoodsSpecModel;

use common\library\Basewind;
use common\library\Language;

use apiserver\library\Respond;

/**
 * @Id WholesaleController.php 2021.5.13 $
 * @author yxyc
 */

class WholesaleController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取指定商品批发价格
	 * @api 接口访问地址: http://api.xxx.com/wholesale/price
	 */
    public function actionPrice()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['goods_id', 'spec_id', 'quantity']);
		$post->goods_id = $this->getGoodsId($post);

		$list =  WholesaleModel::find()->where(['goods_id' => $post->goods_id, 'closed' => 0])->orderBy(['quantity' => SORT_ASC])->all();
		foreach($list as $key => $value) {
			$this->params['list'][] = array('price' => $value['price'], 'min' => $value['quantity'], 'max' => (isset($list[$key+1]) && $list[$key+1]['quantity'] > 1) ? $list[$key+1]['quantity']-1 : 0);
		}

		// 读取商品批发价格
		$query = WholesaleModel::find()->where(['goods_id' => $post->goods_id, 'closed' => 0])->andWhere(['<=', 'quantity', $post->quantity])->orderBy(['quantity' => SORT_DESC])->one();
		if($query) {
			$this->params = array_merge($this->params, [
				'price' => round($query->price, 2),
				'quantity' => $query->quantity
			]);
		}

		return $respond->output(true, null, $this->params);
    }

	private function getGoodsId($post = null) 
	{
		if(isset($post->goods_id) && $post->goods_id) {
			return $post->goods_id;
		}

		return GoodsSpecModel::find()->select('goods_id')->where(['spec_id' => $post->spec_id])->scalar();
	}
}