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
use common\models\GoodsModel;
use common\models\GoodsQaModel;
use common\models\SgradeModel;
use common\models\UploadedFileModel;
use common\models\RefundModel;
use common\models\CollectModel;

use common\library\Timezone;
use common\library\Def;

/**
 * @Id UserForm.php 2018.3.25 $
 * @author mosir
 */
class UserForm extends Model
{
	public $userid = 0;
	public $errors = null;
	
    public function valid($post)
	{
		return true;
	}
	
	/**
	 * 买家提醒：待付款、待确认、待评价等
	 * @desc API接口也使用到该数据	 
	 */
	public function getBuyerStat()
	{
		$array = array(
			'pending'  => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_PENDING])->count(),
			'accepted' => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_ACCEPTED])->count(),
            'shipped'  => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_SHIPPED])->count(),
            'finished' => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED])->count(),
            'evaluation' => OrderModel::find()->where(['buyer_id' => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED, 'evaluation_status' => 0])->count(),
			'my_question' => GoodsQaModel::find()->where(['userid' => Yii::$app->user->id, 'if_new' => 1])->andWhere(['!=', 'reply_content', ''])->count(), // for mobile
			'refund'  => RefundModel::find()->where(['and', ['buyer_id' => Yii::$app->user->id], ['not in', 'status', ['SUCCESS', 'CLOSED']]])->count() // for mobile
        );
		return $array;
	}
	
	/**
	 * 卖家提醒：已提交、待发货、待回复等
	 * @desc API接口也使用到该数据
	 */
	public function getSellerStat()
	{
		$array = array(
			'pending'  => OrderModel::find()->where(['seller_id' => Yii::$app->user->id, 'status' => Def::ORDER_PENDING])->count(),
      		'submitted' => OrderModel::find()->where(['seller_id' => Yii::$app->user->id, 'status' => Def::ORDER_SUBMITTED])->count(),
      		'accepted'  => OrderModel::find()->where(['seller_id' => Yii::$app->user->id, 'status' => Def::ORDER_ACCEPTED])->count(),
    		'shipped'  => OrderModel::find()->where(['seller_id' => Yii::$app->user->id, 'status' => Def::ORDER_SHIPPED])->count(),
			'finished' => OrderModel::find()->where(['seller_id' => Yii::$app->user->id, 'status' => Def::ORDER_FINISHED, 'evaluation_status' => 0])->count(),
			'replied'   => GoodsQaModel::find()->where(['store_id' => Yii::$app->user->id])->andWhere(['=', 'reply_content', ''])->count(),
			'refund'  => RefundModel::find()->where(['and', ['seller_id' => Yii::$app->user->id], ['not in', 'status', ['SUCCESS', 'CLOSED']]])->count() // for mobile
 		);
		 return $array;
	}
	
	/* 店铺等级、有效期、商品数、空间 */
	public function getSgrade($store = array())
	{
		$grade = SgradeModel::find()->where(['grade_id' => $store['sgrade']])->asArray()->one();
    	$goodsCount = GoodsModel::getCountOfStore($store['store_id']);
   		$spaceSum = UploadedFileModel::find()->where(['store_id' => $store['store_id']])->sum('file_size');
			
     	$sgrade = array(
      		'grade_name' => $grade['grade_name'],
        	'add_time' 	 => empty($store['end_time']) ? 0 : sprintf('%.2f', ($store['end_time'] - Timezone::gmtime())/86400),
        	'goods' => array(
       			'used' 	=> $goodsCount,
          		'total' => $grade['goods_limit']
			),
    		'space' => array(
          		'used' 	=> sprintf("%.2f", floatval($spaceSum)/(1024 * 1024)),
        		'total' => $grade['space_limit']
			),
 		);
		return $sgrade;
	}
	
	/* 目前仅移动端用户中心首页用到 */
	public function getCollect()
	{
		$result = array(
			'count_collect_goods' => CollectModel::find()->alias('c')->joinWith('goods g', false)->where(['userid' => Yii::$app->user->id, 'c.type' => 'goods'])->count(),
			'count_collect_store' => CollectModel::find()->alias('c')->joinWith('goods g', false)->where(['userid' => Yii::$app->user->id, 'c.type' => 'store'])->count(),
			'count_footmark'	  => count(GoodsModel::history())
		);
		return $result;		
	}
}
