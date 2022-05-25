<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\controllers;

use Yii;
use yii\helpers\ArrayHelper;

use common\models\GoodsModel;
use common\models\GcategoryModel;
use common\models\MealGoodsModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id MealController.php 2018.7.20 $
 * @author MH
 */

class MealController extends \common\controllers\BaseMallController
{
	/**
	 * 初始化
	 * @var array $view 当前视图
	 * @var array $params 传递给视图的公共参数
	 */
	public function init()
	{
		parent::init();
		$this->view  = Page::setView('mall');
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('mall'), [
			'navs'	=> NavigationModel::getList()
		]);
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\MealForm(['id' => $post->id]);
		list($meals) = $model->formData($post, true, ['meal_id' => SORT_DESC]);
		if($meals == false) {
			return Message::warning($model->errors);
		}
		$this->params['meal'] = current($meals);
		if($post->goods_id) {
			$this->params['meals'] = MealGoodsModel::find()->alias('mg')->select('m.meal_id,m.title')->where(['goods_id' => $post->goods_id])->joinWith('meal m', false)->orderBy(['m.meal_id' => SORT_DESC])->asArray()->all();
		}
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
			
		$this->params['_head_tags'] = Resource::import('meal.js');
		$this->params['page'] = Page::seo(['title' => Language::get('meal_detail')]);
       	return $this->render('../meal.index.html', $this->params);	
	}
	    
	public function actionSpecinfo()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['goods_id', 'num', 'specQty']);
		if (!$post->goods_id || !$post->spec_2 || !$post->num || !$post->specQty){
            return;
        }
		$goods = GoodsModel::find()->with('goodsSpec')->select('goods_id,goods_name,default_image,price,spec_name_1,spec_name_2,default_spec,spec_qty')->where(['goods_id' => $post->goods_id])->asArray()->one();
		
		foreach($goods['goodsSpec'] as $spec)
		{
			// 两个属性项 比较两个
			if($post->num == 2 && $post->specQty == 2) {
				if($post->spec_1 == $spec['spec_1'] && $post->spec_2 == $spec['spec_2']) {
					return Message::result(['spec' => $spec]);
				}
			}
			else
			{
				if($post->spec_2 == $spec['spec_1']) {
					return Message::result(['spec' => $spec]);
				}
			}
		}
	}
}