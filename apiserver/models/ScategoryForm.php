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

use common\models\ScategoryModel;

use common\library\Language;
use common\library\Page;
use yii\helpers\ArrayHelper;

/**
 * @Id ScategoryForm.php 2018.10.20 $
 * @author yxyc
 */
class ScategoryForm extends Model
{
	public $cate_id = 0;
	public $errors = null;
	
	public function formData($post = null, $ifpage = false, $querychild = false)
	{
		$query = ScategoryModel::find()->where(['>', 'cate_id', 0])->orderBy(['sort_order' => SORT_ASC, 'cate_id' => SORT_ASC]);
		if(isset($post->parent_id)) {
			$query->andWhere(['parent_id' => $post->parent_id]);
		}
		if(isset($post->if_show) && in_array($post->if_show, [0,1])) {
			$query->andWhere(['if_show' => $post->if_show]);
		}
		
		// 读取下级分类用到
		if($ifpage) {
			$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		} else $list = $query->asArray()->all();
		
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
			$model = ScategoryModel::find()->where(['cate_id' => $this->cate_id])->one();
		} 
		else {
			$model = new ScategoryModel();
		}
		
		if(isset($post->cate_name)) $model->cate_name = $post->cate_name;
		if(isset($post->parent_id)) $model->parent_id = $post->parent_id;
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

		return ArrayHelper::toArray($model);
	}
	
	public function exists($post)
	{
		if(!ScategoryModel::find()->where(['cate_id' => $this->cate_id])->exists()) {
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
