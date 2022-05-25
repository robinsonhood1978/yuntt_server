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

use common\models\AddressModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use apiserver\library\Respond;

/**
 * @Id AddressController.php 2018.10.15 $
 * @author yxyc
 */

class AddressController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取用户收货地址列表
	 * @api 接口访问地址: http://api.xxx.com/address/list
	 */
    public function actionList()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);
		
		$query = AddressModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['defaddr' => SORT_DESC, 'addr_id' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach($list as $key => $value) {
			$list[$key] = array_merge($value, RegionModel::getArrayRegion($value['region_id'], $value['region_name']));
			unset($list[$key]['region_name']);
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, Language::get('address_list'), $this->params);
    }
	
	/**
	 * 获取收货地址单条信息
	 * @api 接口访问地址: http://api.xxx.com/address/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id']);
		
		$record = AddressModel::find()->where(['userid' => Yii::$app->user->id, 'addr_id' => $post->addr_id])->asArray()->one();
		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);

		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 插入收货地址信息
	 * @api 接口访问地址: http://api.xxx.com/address/add
	 */
    public function actionAdd()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['region_id']);
		// print_r($post);
		// exit;
		
		$model = new \apiserver\models\AddressForm();		
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		
		if(($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_add_fail'));
		}
		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 更新收货地址信息
	 * @api 接口访问地址: http://api.xxx.com/address/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id', 'region_id']);
		
		$model = new \apiserver\models\AddressForm(['addr_id' => $post->addr_id]);
		if(!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if(($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_update_fail'));
		}
		$this->params = array_merge($record, RegionModel::getArrayRegion($record['region_id'], $record['region_name']));
		unset($this->params['region_name']);
		
		return $respond->output(true, null, $this->params);
	}
	
	/**
	 * 删除收货地址信息
	 * @api 接口访问地址: http://api.xxx.com/address/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['addr_id']);
		
		if(!AddressModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['addr_id' => $post->addr_id]])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('address_delete_fail'));
		}
		
		return $respond->output(true);	
	}
}