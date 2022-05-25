<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\models;

use Yii;
use yii\base\Model; 

use common\models\GcategoryModel;
use common\models\GuideshopModel;

use common\library\Language;
use common\library\Page;

use apiserver\library\Formatter;

/**
 * @Id CategoryForm.php 2018.10.20 $
 * @author yxyc
 */
class CategoryForm extends Model
{
	public $cate_id = 0;
	public $errors = null;
	
	/**
	 * 获取分类数据
	 * @desc 当querychild=true时，读取下级的数据不应该分页
	 */
	public function formData($post = null, $ifpage = false, $querychild = false)
	{
		$query = GcategoryModel::find()->where(['>', 'cate_id', 0])->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC]);
		if(isset($post->parent_id)) {
			$query->andWhere(['parent_id' => $post->parent_id]);
		}

		// 指定社区团购类目
		if($post->channel == 'community') {
			if(!$querychild && ($childs = GuideshopModel::getCategoryId(true, false)) !== false && !in_array($post->parent_id, $childs)) {
				$query->andWhere(['in', 'cate_id', $childs]);
			}
		}

		if(!isset($post->store_id)) {
			$query->andWhere(['store_id' => 0]);
		}
		else {
			$query->andWhere(['store_id' => $post->store_id]);
		}
		if(isset($post->groupid) && $post->groupid) {
			$query->andWhere(['groupid' => $post->groupid]);
		}
		if(isset($post->if_show) && in_array($post->if_show, [0,1])) {
			$query->andWhere(['if_show' => $post->if_show]);
		}
		
		if($ifpage) {
			$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		} else $list = $query->asArray()->all();
		
		foreach($list as $key => $value) {
			$list[$key]['image'] = Formatter::path($value['image']);

			// 读取下级的时候， 不返回ad字段
			if($querychild) {
				unset($list[$key]['ad'], $list[$key]['groupid']);
			} else {
				$list[$key]['ad'] = Formatter::path($value['ad']);
			}
		}
		
		return array($list, $page);
	}
	
	/* 
	 * 编辑状态下，允许只修改其中某项目
	 * 即编辑状态下，不需要对未传的参数进行验证
	 */
	public function valid($post)
	{
		// 新增时必填字段
		$fields = ['cate_name'];
		
		// 空值判断
		foreach($fields as $field) {
			if($this->isempty($post, $field)) {
				$this->errors = Language::get($field.'_required');
				return false;
			}
		}
		
		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !($this->valid($post))) {
			return false;
		}
		
		if($this->cate_id) {
			$model = GcategoryModel::find()->where(['cate_id' => $this->cate_id])->one();
		} 
		else {
			$model = new GcategoryModel();
		}
		
		if(isset($post->cate_name)) $model->cate_name = $post->cate_name;
		if(isset($post->parent_id)) $model->parent_id = $post->parent_id;
		if(isset($post->store_id)) $model->store_id = $post->store_id;
		if(isset($post->groupid)) $model->groupid = $post->groupid;
		if(isset($post->image)) $model->image = $post->image;
		if(isset($post->if_show)) {
			$model->if_show = $post->if_show;
		} elseif(!$this->cate_id) {
			$model->if_show = 1;
		}
		if(isset($post->sort_order)) {
			$model->sort_order = $post->sort_order;
		} elseif(!$this->cate_id) {
			$model->sort_order = 255;
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		$this->cate_id = $model->cate_id;
		
		return true;	
	}
	
	public function exists($post)
	{
		if(!GcategoryModel::find()->where(['cate_id' => $this->cate_id])->exists()) {
			$this->errors = Language::get('category_invalid');
			return false;
		}
		return true;
	}
	
	/*
	 * 如果是新增，则一律判断
	 * 如果是编辑，则设置值了才判断
	 */
	public function isempty($post, $fields)
	{
		if($this->cate_id) {
			if(isset($post->$fields)) {
				return empty($post->$fields);
			}
			return false;
		}
		return empty($post->$fields);
	}
}
