<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_channel2_special_goods;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\LimitbuyModel;

use common\library\Timezone;
use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_channel2_special_goodsWidget extends BaseWidget
{
    var $name = 'df_channel2_special_goods';
    var $_ttl  = 1800;

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$num = intval($this->options['num']) > 0 ? intval($this->options['num']) : 6;

			$limitbuyList = LimitbuyModel::find()->where(['<', 'start_time', Timezone::gmtime()])->andWhere(['>', 'end_time', Timezone::gmtime()])->limit($num)->asArray()->all(); 
			
			if($limitbuyList)
			{
				foreach($limitbuyList as $key => $limitbuy)
				{					
					$goods = GoodsModel::find()->select('default_image, goods_id, goods_name,default_spec, price')->where(['goods_id' => $limitbuy['goods_id']])->asArray()->one();
					
					if($goods) {
						empty($goods['default_image']) && $goods['default_image'] = Yii::$app->params['default_goods_image'];
						$limitbuyList[$key] = ArrayHelper::merge($limitbuyList[$key], $goods);
					} else {
						// 没有商品，删除这个促销
						unset($limitbuyList[$key]);
						LimitbuyModel::findOne($limitbuy['id'])->delete();
					}
					
					list($proPrice) = LimitbuyModel::getItemProPrice($goods['goods_id'], $goods['default_spec']);
					if($proPrice) {
						$limitbuyList[$key]['pro_price'] = $proPrice;
					}
					unset($limitbuyList[$key]['rules']);	
				}
				$goods_list = $limitbuyList;
			}
			else
			{
				$goods_list = $this->getRecommendGoods(intval($this->options['img_recom_id']), $num, true, intval($this->options['img_cate_id']));
			}
			
			$data = array(
				'model_id'	 => mt_rand(),
				'model_name' => $this->options['model_name'],
				'goods_list' => $goods_list,
			);

           $cache->set($key, $data, $this->ttl);
        }
        return $data;
    }

    public function getConfigDataSrc()
    {
		// 取得推荐类型
		$this->params['recommends'] = $this->getRecommendOptions(true);
		
        // 取得一级商品分类
		$this->params['gcategories'] = $this->getGcategoryOptions(0, 0);
    }

	public function parseConfig($input)
    {
        if ($input['img_recom_id'] >= 0)
        {
            $input['img_cate_id'] = 0;
        }

        return $input;
    }
}