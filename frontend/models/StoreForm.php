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

use common\models\GoodsModel;
use common\models\StoreModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Def;

/**
 * @Id StoreForm.php 2018.10.13 $
 * @author mosir
 */
class StoreForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 10)
	{
		if(!$post->id || !($store = StoreModel::getStoreAssign($post->id))) {
			$this->errors = Language::get('the_store_not_exist');
			return false;
		}
		if ($store['state'] == Def::STORE_CLOSED) {
			$this->errors = Language::get('the_store_is_closed');
			return false;
    	}
		if ($store['state'] == Def::STORE_APPLYING) {
			$this->errors = Language::get('the_store_is_applying');
			return false;
		}

		// 取得推荐商品
		$store['recommendedGoods'] = $this->getGoodsList($post->id, 'recommend', $pageper);
		
		// 取得热卖商品
		$store['saleGoods'] = $this->getGoodsList($post->id, 'sale', 10);
		
		return $store;
	}
	
	private function getGoodsList($store_id = 0, $gType = 'recommend', $pageper = 10, $cached = true)
	{
		$cache = Yii::$app->cache;
		$cachekey = md5((__METHOD__).var_export(func_get_args(), true));
		$data = $cache->get($cachekey);
		if($data === false || !$cached)
		{
			// 取得推荐商品
			$query = GoodsModel::find()->alias('g')->select('g.goods_id,goods_name,default_image,price,sales,comments')->joinWith('goodsStatistics gst', false)->where(['store_id' => $store_id, 'if_show' => 1, 'closed' => 0])->limit($pageper);
			
			if(in_array($gType, ['recommend'])) {
				$query->andWhere(['recommended' => 1])->orderBy(['g.goods_id' => SORT_DESC]);
			}
			// 取得最新商品
			if(in_array($gType, ['new'])) {
				$query->orderBy(['g.goods_id' => SORT_DESC]);
			}
			// 取得热卖商品
			if(in_array($gType, ['sale'])) {
				$query->orderBy(['gst.sales' => SORT_DESC, 'g.goods_id' => SORT_DESC]);
			}
			
			// 取得热门商品
			if(in_array($gType, ['hot'])) {
				$query->orderBy(['gst.views' => SORT_DESC, 'g.goods_id' => SORT_DESC]);
			}
			
			$data = $query->asArray()->all();
			foreach($data as $key => $goods) {
				empty($goods['default_image']) && $data[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			}
			
			$cache->set($cachekey, $data, 3600);
		}
		
		return $data;
	}
}
