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

use common\library\Language;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id My_favoriteStoreForm.php 2018.10.7 $
 * @author mosir
 */
class My_favoriteStoreForm extends Model
{
	public $errors = null;

	public function formData($post = null, $pageper = 4)
	{
		$query = CollectModel::find()->alias('c')->select('c.*,s.store_id,s.store_name,s.store_logo,s.credit_value')->joinWith('store s', false)->where(['userid' => Yii::$app->user->id, 'type' => 'store'])->orderBy(['add_time' => SORT_DESC]);
		$query = $this->getConditions($post, $query);
	
		$page = Page::getPage($query->count(), $pageper);
		$recordlist = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($recordlist as $key => $record)
		{
			empty($record['store_logo']) && $recordlist[$key]['store_logo'] = Yii::$app->params['default_store_logo'];
			$recordlist[$key]['credit_image'] = Resource::getThemeAssetsUrl('images/credit/' . StoreModel::computeCredit($record['credit_value']));
			
			$goodslist = GoodsModel::find()->select('goods_id,default_image')->where(['store_id' => $record['store_id'], 'if_show' => 1, 'closed' => 0])->orderBy(['goods_id' => SORT_DESC])->limit(10)->asArray()->all();
			foreach($goodslist as $k => $v) {
				empty($v['default_image']) && $goodslist[$k]['default_image'] = Yii::$app->params['default_goods_image'];
			}
			
			$recordlist[$key]['goodslist'] = $goodslist;
			
		}
		return array($recordlist, $page);
	}
	
	public function addCollect($post = null)
    {
		// 验证店铺是否存在
		if(!($store = StoreModel::find()->select('store_id')->where(['store_id' => $post->item_id])->one())) {
			$this->errors = Language::get('no_such_store');
			return false;
		}
		if(!($model = CollectModel::find()->where(['userid' => Yii::$app->user->id, 'type' => 'store', 'item_id' => $post->item_id])->one())) {
			$model = new CollectModel();
		}
		$model->userid = Yii::$app->user->id;
		$model->type = 'store';
		$model->item_id = $post->item_id;
		$model->keyword = $post->keyword ? $post->keyword : '';
		$model->add_time = Timezone::gmtime();
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('collect_store_fail');
			return false;
		}
        return true;
    }

	public function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['store_name'])) {
					return true;
				}
			}
			return false;
		}
		
		if($post->store_name) {
			$query->andWhere(['like', 'store_name', $post->store_name]);
		}
		
		return $query;
	}
}
