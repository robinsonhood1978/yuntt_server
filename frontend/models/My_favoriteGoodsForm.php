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
use yii\helpers\ArrayHelper;

use common\models\CollectModel;
use common\models\StoreModel;
use common\models\GoodsModel;
use common\models\GoodsStatisticsModel;

use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_favoriteGoodsForm.php 2018.10.7 $
 * @author mosir
 */
class My_favoriteGoodsForm extends Model
{
	public $errors = null;

	public function formData($post = null, $pageper = 4)
	{
		$query = CollectModel::find()->alias('c')->select('c.*,g.goods_id,g.goods_name,g.price,g.default_image,g.store_id,g.cate_id')->joinWith('goods g', false)->where(['userid' => Yii::$app->user->id, 'c.type' => 'goods'])->orderBy(['c.add_time' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
	
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $record)
		{
			empty($record['default_image']) && $recordlist[$key]['default_image'] = Yii::$app->params['default_goods_image'];
			$recordlist[$key]['store_name'] = StoreModel::find()->select('store_name')->where(['store_id' => $record['store_id']])->scalar();
		}
		return array($recordlist, $page);
	}
	
	public function addCollect($post = null)
    {
        // 验证商品是否存在
		if(!($goods = GoodsModel::find()->select('goods_id,goods_name')->where(['goods_id' => $post->item_id])->one())) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}
		if(!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'goods', 'item_id' => $post->item_id])->one())) {
			$model = new CollectModel();
		}
		$model->userid = Yii::$app->user->id;
		$model->type = 'goods';
		$model->item_id = $post->item_id;
		$model->keyword = $post->keyword ? $post->keyword : '';
		$model->add_time = Timezone::gmtime();
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('collect_goods_fail');
			return false;
		}
		// 更新被收藏总次数
		GoodsStatisticsModel::updateStatistics($post->item_id, 'collects');
	
        return true;
    }

	public function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['goods_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->store_name) {
			$query->andWhere(['like', 'goods_name', $post->store_name]);
		}
		
		return $query;
	}
}
