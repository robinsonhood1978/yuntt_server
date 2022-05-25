<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\helpers\Url;

use common\models\NavigationModel;

use common\library\Language;

/**
 * @Id NavigationForm.php 2018.8.23 $
 * @author mosir
 */
class NavigationForm extends Model
{
	public $nav_id = 0;
	public $errors = null;
	
	public function valid($post)
	{
		if(empty($post->title)) {
			$this->errors = Language::get('title_empty');
			return false;
		}
		if(!in_array($post->type, ['header', 'middle', 'footer'])) {
			$this->errors = Language::get('nav_type_invalid');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->nav_id || !($model = NavigationModel::findOne($this->nav_id))) {
			$model = new NavigationModel();
		}
		
        $model->title = $post->title;
		$model->type = $post->type;
		$model->if_show = $post->if_show; 
		$model->sort_order = $post->sort_order ? $post->sort_order : 255;
		$model->open_new = $post->open_new;
		
		if(isset($post->gcategory_cate_id) && ($post->gcategory_cate_id > 0)) {
			$model->link = str_replace(Yii::$app->homeUrl, '', Url::toRoute(['search/index', 'cate_id' => $post->gcategory_cate_id]));
		}
		elseif(isset($post->acategory_cate_id) && ($post->acategory_cate_id > 0)) {
			$model->link = str_replace(Yii::$app->homeUrl, '', Url::toRoute(['article/index', 'cate_id' => $post->acategory_cate_id]));
		} else $model->link = $post->link ? $post->link : '';

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}

		return $model;
	}
}
