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

use common\models\GcategoryModel;
use common\models\ScategoryModel;
use common\models\NavigationModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id CategoryController.php 2018.7.3 $
 * @author mosir
 */

class CategoryController extends \common\controllers\BaseMallController
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
		// 商品分类
		$this->params['categories'] = GcategoryModel::getTree(0, true, 2);
		
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
	
		$this->params['page'] = Page::seo(['title' => Language::get('category_goods')]);
		return $this->render('../category.goods.html', $this->params);
	}

	public function actionStore()
	{
		// 头部商品分类
		$this->params['gcategories'] = GcategoryModel::getGroupGcategory();
		
		// 店铺分类
		$this->params['scategories'] = ScategoryModel::getTree();
		
		$this->params['page'] = Page::seo(['title' => Language::get('category_store')]);
		return $this->render('../category.store.html', $this->params);
	}

	/**
	 * 前台页面所有分类AJAX数据
	 */
	public function actionList()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$allId = explode('|', $post->allId);
		if(empty($allId)) $allId = array();

		$result = array();
		foreach($allId as $item)
		{
			$parent_id = -1;

			list($groupid, $cate_id) = explode(',', $item);
			if(is_numeric($groupid) && $groupid > 0) {
				$parent_id = GcategoryModel::find()->select('cate_id')->where(['groupid' => $groupid, 'if_show' => 1])->column();
			} elseif(is_numeric($cate_id) && $cate_id > 0) {
				$parent_id = [$cate_id];
			}
			$list = GcategoryModel::find()->select('cate_id,cate_name')->where(['in', 'parent_id', $parent_id])
				->andWhere(['if_show' => 1])->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC])->asArray()->all();
			foreach($list as $key => $value) {
				$list[$key]['children'] = GcategoryModel::getList($value['cate_id'], 0, true, 0, 'cate_id,cate_name');
			}
			$result[$item] = $list;
		}
		
		return Message::result($result);
	}
}