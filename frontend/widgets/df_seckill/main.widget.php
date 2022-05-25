<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\widgets\df_seckill;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\LimitbuyModel;
use common\models\GoodsStatisticsModel;
use common\models\GuideshopModel;

use common\library\Timezone;
use common\library\Promotool;

use common\widgets\BaseWidget;

/**
 * @Id main.widget.php 2018.9.7 $
 * @author mosir
 */
 
class Df_seckillWidget extends BaseWidget
{
    var $name = 'df_seckill';

    public function getData()
    {
        $cache = Yii::$app->cache;
        $key = $this->getCacheId();
		$data = $cache->get($key);
        if($data === false)
        {
			$limit = $this->options['num'] ? intval($this->options['num']) : 10;
			
			$query = LimitbuyModel::find()->alias('lb')->select('lb.*,g.goods_name,g.default_image,g.price,g.default_spec')->joinWith('goods g', false)->where(['g.if_show' => 1, 'g.closed' => 0])->andWhere(['and', ['<=', 'start_time', Timezone::gmtime()], ['>=', 'end_time', Timezone::gmtime()]]);
			
			// 因社区团购购买流程只在移动端体现，所以PC端排除社区团购商品，如果无需排除，可注释该代码
			$childs = GuideshopModel::getCategoryId(true);
			if($childs !== false) {
				$query->andWhere(['not in', 'cate_id', $childs]);
			}

			if($this->options['goods_id']) {
				$query->andWhere(['in','g.goods_id', explode('|',$this->options['goods_id'])]);
			}
			
			$limitbuy_list = $query->limit($limit)->orderBy(['id' => SORT_DESC])->asArray()->all();
			
			// 如果没有促销商品（补够）
			if(empty($limitbuy_list) || (count($limitbuy_list) < 5)) {
				$list = GoodsModel::find()->select('goods_id,goods_name,price,default_image')->where(['if_show' => 1, 'closed' => 0])->andWhere(['not in', 'cate_id', $childs !== false ? $childs : [0]])->limit(6 - count($limitbuy_list))->asArray()->all();
				foreach($list as $item) {
					$limitbuy_list[] = $item;
				}
			}
			$time = [Timezone::gmtime() + 3600];
			$promotool = Promotool::getInstance()->build();
			foreach ($limitbuy_list as $key => $limitbuy)
			{
				$result = $promotool->getItemProInfo($limitbuy['goods_id'], $limitbuy['default_spec']);
				if($result !== false) {
					$limitbuy_list[$key]['pro_price'] = $result['price'];
				}
				
				if($limitbuy['image']) {
					$limitbuy_list[$key]['default_image'] = $limitbuy['image'];
				}
				else {
					$limitbuy['default_image'] || $limitbuy_list[$key]['default_image'] = Yii::$app->params['default_goods_image'];
				}
				
				$time[] = $limitbuy['end_time'];
			}
		
			$data = array(
				'model_id' 		=> mt_rand(), 
				'model_name' 	=> $this->options['model_name'] ? $this->options['model_name'] : '限时抢购',
				'goods_list' 	=> $limitbuy_list,
				'lefttime' 		=> Timezone::lefttime(max($time))
			);
			$cache->set($key, $data, $this->ttl);
        }
		return $data;
    }
}
