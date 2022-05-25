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

use common\models\GoodsSpecModel;

use common\library\Basewind;
use common\library\Page;

/**
 * @Id TeambuyModel.php 2019.5.21 $
 * @author mosir
 */

class TeambuyModel extends ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return '{{%teambuy}}';
    }
	
	// 关联表
	public function getGoods()
	{
		return parent::hasOne(GoodsModel::className(), ['goods_id' => 'goods_id']);
	}
	// 关联表
	public function getStore()
	{
		return parent::hasOne(StoreModel::className(), ['store_id' => 'store_id']);
	}
	// 关联表
	public function getGoodsStatistics()
	{
		return parent::hasOne(GoodsStatisticsModel::className(), ['goods_id' => 'goods_id']);
	}
	
	public static function getList($store_id = 0, $params = array(), &$pagination = false)
	{
		$query = parent::find()->alias('tb')->select('tb.id,tb.goods_id,tb.store_id,tb.title,tb.specs,tb.status,g.goods_name,g.default_image,g.price,g.default_spec')->joinWith('goods g', false)->where(['g.if_show' => 1, 'g.closed' => 0]);
		if($store_id > 0) $query->andWhere(['tb.store_id' => $store_id]);
		if($params) $query->andWhere($params);
		
		if($pagination !== false) {
			// 分页
			if(isset($pagination['pageSize']) && $pagination['pageSize'] > 0) {
				$page = Page::getPage($query->count(), $pagination['pageSize']);
				$query->offset($page->offset)->limit($page->limit);
				$pagination = $page;
			}
			// 指定数量
			elseif(is_numeric($pagination) && ($pagination > 0)) {
				$query->limit($pagination);
			}
		}
		$teambuys = $query->orderBy(['id' => SORT_DESC])->asArray()->all();
		
		foreach ($teambuys as $key => $teambuy)
        {
			$teambuys[$key]['specs'] = unserialize($teambuy['specs']);
			
			list($proPrice) = self::getItemProPrice($teambuy['default_spec']);
			if($proPrice !== false) $teambuys[$key]['pro_price'] = $proPrice;
			
			// 判断状态
			$teambuys[$key]['status'] = self::getTeambuyStatus($teambuy, true);
        }

		return $teambuys;
	}
	
	/** 
	 * 获取拼团价格
	 */
	public static function getItemProPrice($spec_id = 0)
	{
		if(!$spec_id) {
			return array(false, 0);
		}
		
		if(!($spec = GoodsSpecModel::find()->select('price,goods_id')->where(['spec_id' => $spec_id])->one())) {
			return array(false, 0);
		}
	
		$query = parent::find()->select('id,specs')->where(['goods_id' => $spec->goods_id])->orderBy(['id' => SORT_DESC]);
		if(($teambuy = $query->one()))
		{	
			$specPrice = unserialize($teambuy->specs);
			$proPrice = round($spec->price * $specPrice[$spec_id]['price'] / 1000, 4) * 100;

			return array($proPrice, $teambuy->id);
		}
		
		return array(false, 0);
	}
	
	/**
	 * 判断拼团状态
	 */
	public static function getTeambuyStatus($data, $checkPrice = false)
	{
		if(is_array($data)) $teambuy = Basewind::trimAll($data, true);
		else $teambuy = parent::find()->select('goods_id,specs,status')->where(['id' => intval($data)])->one(); // data = id
	
		if(!$teambuy->status) {
			return false;
		}

		// 简单验证价格
		if($checkPrice) {
			$specs = GoodsSpecModel::find()->select('spec_id,price')->where(['goods_id' => $teambuy->goods_id])->all();
			$teambuy->specs = !is_array($teambuy->specs) ? unserialize($teambuy->specs) : $teambuy->specs;
			
			if(count($teambuy->specs) != count($specs)) {
				return false;
			}
		}

		return true;
	}
}
