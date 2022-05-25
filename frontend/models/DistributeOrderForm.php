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

use common\models\DistributeOrderModel;

use common\library\Def;
use common\library\Page;

/**
 * @Id DistributeOrderForm.php 2018.9.19 $
 * @author mosir
 */
class DistributeOrderForm extends Model
{
	public $errors = null;
	
	public function formData($post = null, $pageper = 4, $isAJax = false, $curPage = false) 
	{
		$query = DistributeOrderModel::find()->alias('do')->select('do.order_sn,do.type,o.order_id,o.order_amount,o.seller_id,o.seller_name')->joinWith('order o', false)->where(['userid' => Yii::$app->user->id])->orderBy(['doid' => SORT_DESC]);
		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		
		if($post->queryitem)
		{
			foreach($list as $key => $value)
			{
				$goodslist = DistributeOrderModel::find()->select('do.tradeNo,do.money,do.layer,do.ratio,og.goods_id,og.goods_name,og.price,og.quantity,og.goods_image,og.specification')->alias('do')->joinWith('orderGoods og', false)->where(['order_sn' => $value['order_sn'], 'type' => $value['type']])->asArray()->all();
				
				$amount = 0;
				foreach($goodslist as $k => $v) {
					$amount += $v['money'];
					$goodslist[$k]['ratio'] = ($v['ratio'] * 100).'%';
					$list[$key]['tradeNo'][] = $v['tradeNo'];
				}
				$list[$key]['amount'] = $amount;
				$list[$key]['items'] = $goodslist;
				$list[$key]['tradeNo'] = implode(',', $list[$key]['tradeNo']);
				$list[$key]['status'] = Def::ORDER_FINISHED;
			}
		}
		
		return array($list, $page);
	}
}
