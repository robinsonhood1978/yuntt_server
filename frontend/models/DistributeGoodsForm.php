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
use common\models\DistributeModel;
use common\models\DistributeItemsModel;
use common\models\DistributeSettingModel;
use common\models\StoreModel;
use common\models\GoodsStatisticsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;
use common\library\Promotool;

/**
 * @Id DistributeGoodsForm.php 2018.10.19 $
 * @author mosir
 */
class DistributeGoodsForm extends Model
{
	public $errors = null;
	
	/**
	 * 分销商获取分销商品（多个店铺的允许分销的商品数据）
	 */
	public function formData($post = null, $pageper = 4, $isAJax = false, $curPage = false) 
	{
		// 可分销的商品
		if($post->type == 'pending') {
			$query = DistributeSettingModel::find()->alias('ds')->select('ds.item_id as goods_id')
				->joinWith('goods g', false, 'INNER JOIN')->where(['enabled' => 1, 'ds.type' => 'goods'])
				->andWhere(['not in', 'item_id', DistributeItemsModel::find()->select('item_id')->where(['userid' => Yii::$app->user->id, 'type' => 'goods'])->column()])
				->orderBy(['dsid' => SORT_DESC]);
		}
		// 已经分销的商品
		else { // going
			$query = DistributeItemsModel::find()->alias('di')->select('di.item_id as goods_id')
				->joinWith('distributeSetting ds', false, 'INNER JOIN')->joinWith('goods g', false, 'INNER JOIN')
				->where(['di.type' => 'goods', 'userid' => Yii::$app->user->id])
				->orderBy(['diid' => SORT_DESC]);
		}
		$query->addSelect('ds.ratio1,ds.ratio2,ds.ratio3,ds.enabled,g.goods_name,g.default_image,g.price,g.store_id');

		if($post->keyword) {
			$query->andWhere(['like', 'g.goods_name', $post->keyword]);
		}
			
		$page = Page::getPage($query->count(), $pageper, $isAJax, $curPage);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value)
		{
			if(Basewind::getCurrentApp() != 'api') {
				$list[$key]['ratio1'] = ($value['ratio1'] * 100) . '%';
				$list[$key]['ratio2'] = ($value['ratio2'] * 100) . '%';
				$list[$key]['ratio3'] = ($value['ratio3'] * 100) . '%';
			}
			$list[$key]['default_image'] = empty($value['default_image']) ? Yii::$app->params['default_goods_image'] : $value['default_image'];

			$list[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $value['store_id']])->scalar();
			if(($record = GoodsStatisticsModel::find()->select('sales')->where(['goods_id' => $value['goods_id']])->asArray()->one())) {
				$list[$key] = array_merge($list[$key], $record);
			}

			// 分销邀请码CODE(每个用户返回的值不同)
			$list[$key]['inviteCode'] = DistributeModel::getInviteCode(['type' => 'goods', 'id' => $value['goods_id'], 'uid' => Yii::$app->user->id]);
		}
		return array($list, $page);
	}

	public function choice($post = null)
	{
		if(($message = Promotool::getInstance('distribute')->build(['store_id' => Yii::$app->user->id])->checkAvailable()) !== true) {
			$this->errors = $message;
			return false;
		}
		
		if(!$post->goods_id || !GoodsModel::find()->where(['if_show' => 1, 'closed' => 0, 'goods_id' => $post->goods_id])->exists()) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}
		if(!DistributeItemsModel::find()->where(['userid' => Yii::$app->user->id, 'item_id' => $post->goods_id, 'type' => 'goods'])->exists()) {
			$model = new DistributeItemsModel();
			$model->userid = Yii::$app->user->id;
			$model->item_id = $post->goods_id;
			$model->type = 'goods';
			$model->created = Timezone::gmtime();
			if(!$model->save()) {
				$this->errors = $model->errors;
				return false;
			}
		}
		return true;
	}
}
