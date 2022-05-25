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

use common\models\GoodsModel;
use common\models\GoodsSpecModel;
use common\models\GoodsImageModel;
use common\models\UploadedFileModel;
use common\models\GcategoryModel;
use common\models\StoreModel;
use common\models\BrandModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id GoodsForm.php 2018.8.14 $
 * @author mosir
 */
class GoodsForm extends Model
{
	public $goods_id = 0;
	public $store_id = 0;
	public $gtype = 'material';
	public $errors = null;
	
	public function valid($post)
	{
		// 不是店家不允许发布商品
		if(!($store = StoreModel::find()->where(['store_id' => $this->store_id, 'state' => 1])->one())) {
			$this->errors = Language::get('not_seller');
			return false;
		}
		if($this->goods_id && (GoodsModel::find()->select('store_id')->where(['goods_id' => $this->goods_id])->scalar() != $this->store_id)) {
			$this->errors = Language::get('no_such_goods');
			return false;
		}

		if(empty($post->goods_name)) {
			$this->errors = Language::get('goods_name_invalid');
			return false;
		}
		if(!$post->cate_id) {
			$this->errors = Language::get('category_invalid');
			return false;
		}

		if(empty(ArrayHelper::toArray($post->goods_images))) {
			$this->errors = Language::get('goods_image_invalid');
			return false;
		}

		if(empty(ArrayHelper::toArray($post->specs)) && (!isset($post->price) || !isset($post->stock))) {
			$this->errors = Language::get('price_invalid');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}
		
		if(!$this->goods_id || !($model = GoodsModel::find()->where(['goods_id' => $this->goods_id, 'store_id' => $this->store_id])->one())) {
			$model = new GoodsModel();
			$model->add_time = Timezone::gmtime();
			$model->store_id = $this->store_id;
			$model->type = $this->gtype;
		} else {
			$model->last_update = Timezone::gmtime();
		}

		$model->goods_name = $post->goods_name;
		$model->content = isset($post->content) ? $post->content : ''; // 手机端纯文本描述
		$model->recommended = intval($post->recommended);
		$model->if_show = intval($post->if_show);
		$model->dt_id = intval($post->dt_id);
		$model->brand = $post->brand;

		$model->cate_id = $post->cate_id;
		if(($list = GcategoryModel::getAncestor($post->cate_id, 0, false))) {
			$ancestor = '';
			foreach($list as $key => $value) {
				$ancestor .= $value['cate_name']. ' ';
			}
			$model->cate_name = trim($ancestor);
		}
		
		if(!$model->save()) {
			$this->errors = $model->errors ? $model->errors : Language::get('save_fail');
			return false;
		}
		
		// 商品主图
		if(($images = $this->formatImages($post, 'goods_images'))) {
	
			foreach($images as $key => $value) {

				if($key == 0) {
					$model->default_image = $value;
					$model->save();
				}

				$query = GoodsImageModel::find()->where(['or', ['thumbnail' => $value], ['image_url' => $value]])->one();
				$query->goods_id = $model->goods_id;
				$query->sort_order = $key > 0 ? 255 : 1;
				if($query->save()) {
					UploadedFileModel::updateAll(['item_id' => $model->goods_id], ['file_id' => $query->file_id]);
				}
			}
		}
		
		// 描述图
		if(($images = $this->formatImages($post, 'desc_images'))) {
			foreach($images as $key => $value) {
				UploadedFileModel::updateAll(['item_id' => $model->goods_id], ['file_path' => $value]);
			}

			// 为了数据一致，覆盖PC端详情字段
			$model->description = $this->getGoodsHtml($model->content, $images);
			$model->save();
		}
		
		// 商品规格
		list($specs, $spec_qty) = $this->formatSpecs($post);
		if($specs) {
			
			$allId = [];
			foreach($specs as $key => $value) {
				$item = $value['spec_1'].$value['spec_2'];
				if(!($query = GoodsSpecModel::find()->where(['goods_id' => $model->goods_id, 'spec_1' => $value['spec_1'], 'spec_2' => $value['spec_2']])->one())) {
					$query = new GoodsSpecModel();
					$query->goods_id = $model->goods_id;
				}

				foreach($value as $k => $v) {
					if(in_array($k, ['spec_1', 'spec_2', 'price', 'mkprice', 'stock'])) {
						$query->$k = $v;
					}
				}
				if($query->save()) {
					if($key == 0) {
						$model->spec_qty = $spec_qty;
						$model->default_spec = $query->spec_id;
						$model->price = $value['price'];
						$model->mkprice = isset($value['mkprice']) ? $value['mkprice'] : 0;
						$model->save();
					}
				}
				$allId[] = $query->spec_id;
			}

			// 删除多余的规格
			GoodsSpecModel::deleteAll(['and', ['goods_id' => $model->goods_id], ['not in', 'spec_id', $allId]]);

			if($spec_qty < 1) {
				$model->spec_name_1 = '';
				$model->spec_name_2 = '';
			} else {
				$model->spec_name_1 = $post->spec_name_1;
				$model->spec_name_2 = $spec_qty > 1 ? $post->spec_name_2 : '';
			}
			$model->save();
		}
		
		return true;
	}

	/**
	 * 格式化上传图片，如果是本地上传（相对于OSS存储），则保存相对路径，不要存绝对路径
	 */
	private function formatImages($post, $field = 'goods_images')
	{
		$images = [];
		foreach($post->$field as $key => $value) {
			if($value) {
				$images[] = str_replace(Basewind::homeUrl() . '/', '',  $value);
			}
		}

		return $images;
	}

	/**
	 * 处理商品规格数据
	 */
	private function formatSpecs($post = null)
	{
		// 没有规格
		if(!isset($post->specs) || empty($specs = ArrayHelper::toArray($post->specs)) || $post->spec_qty <= 0) {
			return array([array(
				'price' => floatval($post->price),
				'mkprice' => isset($post->mkprice) ? floatval($post->mkprice) : 0,
				'stock' => intval($post->stock),
			)], 0);
		}
		
		// 多个规格
		$values = [];
		foreach($specs as $key => $value) {
			$item = $value['spec_1'] . $value['spec_2'];
			if(in_array($item, $values)) {
				$this->errors = Language::get('spec_repeat');
				return false;
			}
			$values[] = $item;

			foreach($value as $k => $v) {
				if(in_array($k, ['price', 'mkprice', 'stock'])) {
					$specs[$key][$k] = floatval($v);
				}
			}
		}

		return array($specs, count($values));
	}

	/**
	 * 获取手机端详情页的Html
	 * @desc 1）因移动端不利于使用编辑器来编辑商品描述，所以PC和移动端的描述字段做了分离处理
	 * 	     2）移动端只支持纯文本描述，PC和移动端共用一致的描述图，将纯文本描述加到描述图前面（类似拼DD做法）
	 * 		 3）如果编辑移动端详情，则会覆盖PC端详情字段，如果编辑PC端详情，则不会覆盖移动端详情
	 */
	private function getGoodsHtml($content = '', $descimages = [])
	{
		$html = '';
		foreach($descimages as $key => $value) {
			$html .= '<img src="'.Page::urlFormat($value).'">';
		}

		return sprintf('<p>%s</p><p>%s</p>', $content, $html);
	}
}
