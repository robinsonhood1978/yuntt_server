<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id CategoryController.php 2018.10.25 $
 * @author yxyc
 */

class CategoryController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
 	 * 获取分类列表
	 * @api 接口访问地址: http://api.xxx.com/category/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['parent_id', 'store_id', 'groupid', 'if_show', 'page', 'page_size']);
		
		$model = new \apiserver\models\CategoryForm();
		list($list, $page) = $model->formData($post, true);

		// 非全量获取的情况下，才允许获取下级
		if(isset($post->querychild) && ($post->querychild === true) && isset($post->parent_id)) {
			foreach($list as $key => $value) {
				$post->parent_id = $value['cate_id'];
				list($children) = $model->formData($post, false, true);
				$list[$key]['children'] = $children;
			}
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, Language::get('category_list'), $this->params);
    }
	
	/**
 	 * 获取分类单条信息
	 * @api 接口访问地址: http://api.xxx.com/category/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id']);
		
		$record = GcategoryModel::find()->where(['cate_id' => $post->cate_id])->asArray()->one();
		if(!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_category'));
		}
		$record['image'] = Formatter::path($record['image']);
		$record['ad'] = Formatter::path($record['ad']);
		
		return $respond->output(true, null, $record);
	}
	
	/**
 	 * 插入分类信息
	 * @api 接口访问地址: http://api.xxx.com/category/add
	 */
    public function actionAdd()
    {
		exit('根据需要开放');

		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['parent_id', 'if_show', 'sort_order', 'store_id']);
		
		$model = new \apiserver\models\CategoryForm();		
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if(!$model->save($post, false)) {
			return $respond->output(Respond::CURD_FAIL, Language::get('category_add_fail'));
		}
		
		return $respond->output(true, null, ['cate_id' => $model->cate_id]);
	}
	
	/**
 	 * 更新分类信息
	 * @api 接口访问地址: http://api.xxx.com/category/update
	 */
    public function actionUpdate()
    {
		exit('根据需要开放');

		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id', 'if_show', 'sort_order', 'store_id']);
		
		$model = new \apiserver\models\CategoryForm(['cate_id' => $post->cate_id]);
		if(!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if(!$model->save($post, false)) {
			return $respond->output(Respond::CURD_FAIL, Language::get('category_update_fail'));
		}

		return $respond->output(true, null, ['cate_id' => $model->cate_id]);
	}
	
	/**
 	 * 删除分类信息
	 * @api 接口访问地址: http://api.xxx.com/category/delete
	 */
    public function actionDelete()
    {
		exit('根据需要开放');
		
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['cate_id']);
		
		if(!GcategoryModel::deleteAll(['cate_id' => $post->cate_id])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('category_delete_fail'));
		}
		
		return $respond->output(true);	
	}
}