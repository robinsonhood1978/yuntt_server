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

use common\models\BrandModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id BrandController.php 2019.1.15 $
 * @author yxyc
 */

class BrandController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取品牌列表
	 * @api 接口访问地址: http://api.xxx.com/brand/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['if_show', 'recommended', 'cate_id', 'page', 'page_size']);
		
		$query = BrandModel::find()->select('brand_id,brand_name,brand_logo,brand_image,cate_id,if_show,tag,letter,recommended,description')
			->where(['store_id' => 0])->orderBy(['sort_order' => SORT_ASC, 'brand_id' => SORT_DESC]);
		
		if(isset($post->if_show) && in_array($post->if_show, [0,1])) {
			$query->andWhere(['if_show' => $post->if_show]);
		}
		if(isset($post->recommended) && in_array($post->recommended, [0,1])) {
			$query->andWhere(['recommended' => $post->recommended]);
		}
		if(isset($post->cate_id) && $post->cate_id) {
			$allId = GcategoryModel::getDescendantIds($post->cate_id);
			$query->andWhere(['in', 'cate_id', $allId]);
		}
		
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key]['brand_logo'] = Formatter::path($value['brand_logo']);
			$list[$key]['brand_image'] = Formatter::path($value['brand_image']);
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, null, $this->params);
    }
	
	/**
	 * 获取品牌单条信息
	 * @api 接口访问地址: http://api.xxx.com/brand/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(false)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['brand_id']);
		
		$record = BrandModel::find()->select('brand_id,brand_name,brand_logo,brand_image,cate_id,if_show,tag,letter,recommended,description')->where(['brand_id' => $post->brand_id])->asArray()->one();
		if(!$record) {
			return $respond->output(Respond::RECORD_NOTEXIST, Language::get('no_such_brand'));
		}
		
		$record['brand_logo'] = Formatter::path($record['brand_logo']);
		$record['brand_image'] = Formatter::path($record['brand_image']);
		
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 插入品牌信息
	 * @api 接口访问地址: http://api.xxx.com/brand/add
	 */
    public function actionAdd()
    {
		
	}
	
	/**
	 * 更新品牌信息
	 * @api 接口访问地址: http://api.xxx.com/brand/update
	 */
    public function actionUpdate()
    {
		
	}
	
	/**
	 * 删除品牌信息
	 * @api 接口访问地址: http://api.xxx.com/brand/delete
	 */
    public function actionDelete()
    {
		
	}
}