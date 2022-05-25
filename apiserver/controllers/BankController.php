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

use common\models\BankModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

use apiserver\library\Respond;

/**
 * @Id BankController.php 2018.10.15 $
 * @author yxyc
 */

class BankController extends Controller
{
	public $layout = false; 
	public $enableCsrfValidation = false;
	
	public $params;
	
	/**
	 * 获取用户银行卡列表
	 * @api 接口访问地址: http://api.xxx.com/bank/list
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
		
		$query = BankModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['bid' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];

		return $respond->output(true, Language::get('bank_list'), $this->params);
	}

	/**
	 * 获取银行卡单条信息
	 * @api 接口访问地址: http://api.xxx.com/bank/read
	 */
    public function actionRead()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['bid']);
		
		$this->params = BankModel::find()->where(['userid' => Yii::$app->user->id, 'bid' => $post->bid])->asArray()->one();
		return $respond->output(true, null, $this->params);
	}
	
	
	/**
	 * 插入银行卡信息
	 * @api 接口访问地址: http://api.xxx.com/bank/add
	 */
    public function actionAdd()
    {
		// 验证签名
		 $respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['num']);
		
		$model = new \apiserver\models\BankForm();		
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		
		if(($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('bank_add_fail'));
		}
		
		return $respond->output(true, null, $record);
	}
	
	/**
	 * 更新银行卡信息
	 * @api 接口访问地址: http://api.xxx.com/bank/update
	 */
    public function actionUpdate()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['bid']);
		
		$model = new \apiserver\models\BankForm(['bid' => $post->bid]);
		if(!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if(!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if(($record = $model->save($post, false)) === false) {
			return $respond->output(Respond::CURD_FAIL, Language::get('bank_update_fail'));
		}

		return $respond->output(true, null, $record);
	}
	
	/**
	 * 删除银行卡
	 * @api 接口访问地址: http://api.xxx.com/bank/delete
	 */
    public function actionDelete()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['bid']);
		
		if(!BankModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['bid' => $post->bid]])) {
			return $respond->output(Respond::CURD_FAIL, Language::get('bank_delete_fail'));
		}
		
		return $respond->output(true);	
	}

	/**
	 * 获取银行卡数量
	 * @api 接口访问地址: http://api.xxx.com/bank/quantity
	 */
    public function actionQuantity()
    {
		// 验证签名
		$respond = new Respond();
		if(!$respond->verify(true)) {
			return $respond->output(false);
		}
		
		$quantity = BankModel::find()->where(['userid' => Yii::$app->user->id])->count();
		return $respond->output(true, null, ['quantity' => $quantity]);
    }
}