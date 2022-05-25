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
use yii\helpers\Url;

use common\models\StoreModel;
use common\models\ArticleModel;
use common\models\RegionModel;
use common\models\ScategoryModel;
use common\models\SgradeModel;
use common\models\GoodsModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;

/**
 * @Id ApplyController.php 2018.10.20 $
 * @author mosir
 */

class ApplyController extends \common\controllers\BaseUserController
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
		$this->params = ArrayHelper::merge($this->params, Page::getAssign('user'));
	}
	
	/* 开店向导 */
    public function actionIndex()
    {
		if($this->checkApply() !== true) {
			return Message::warning($this->errors);
		}

		// 直接进入申请页面
		return $this->redirect(['apply/fill']);
		
		// $this->params['articles'] = ArticleModel::find()->select('title,description')->where(['in', 'article_id', [1,2,3]])->orderBy(['article_id' => SORT_ASC])->asArray()->all();
		
		// // 当前位置
		// $this->params['_curlocal'] = Page::setLocal(Language::get('apply'), Url::toRoute('apply/index'), Language::get('apply_index'));
			
		// // 当前用户中心菜单
		// $this->params['_usermenu'] = Page::setMenu('apply', 'apply_index');
			
		// $this->params['page'] = Page::seo(['title' => Language::get('apply_index')]);
        // return $this->render('../apply.index.html', $this->params);
	}
	
	/* 签署协议 */
	public function actionAgreement()
	{
		if($this->checkApply() !== true) {
			return Message::warning($this->errors);
		}
		
		$this->params['article'] = ArticleModel::find()->select('title,description')->where(['article_id' => 4])->asArray()->one();
			
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('apply'), Url::toRoute('apply/index'), Language::get('apply_agreement'));
			
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('apply', 'apply_agreement');
			
		$this->params['page'] = Page::seo(['title' => Language::get('apply_agreement')]);
        return $this->render('../apply.agreement.html', $this->params);
	}
	
	/**
	 * 填写店铺信息
	 */
	public function actionFill()
	{
		if($this->checkApply() !== true) {
			return Message::warning($this->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->indexBy('region_id')->column();
			$this->params['scategories'] = ScategoryModel::getOptions(-1, null, 2);
			
			$sgrades = SgradeModel::find()->orderBy(['need_confirm' => SORT_ASC, 'sort_order' => SORT_ASC, 'grade_id' => SORT_ASC])->asArray()->all();
			foreach ($sgrades as $key => $sgrade)
			{
				if (!$sgrade['goods_limit']) {
					$sgrades[$key]['goods_limit'] = Language::get('no_limit');
				}
				if (!$sgrade['space_limit']) {
					$sgrades[$key]['space_limit'] = Language::get('no_limit');
				}			
			}		
			$this->params['sgrades'] = $sgrades;
			
			// for edit
			$this->params['store'] = StoreModel::find()->alias('s')->select('s.*,cs.cate_id')->joinWith('categoryStore cs',false)->where(['s.store_id' => Yii::$app->user->id])->asArray()->one();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js,mlselection.js');
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('apply'), Url::toRoute('apply/index'), Language::get('apply_fill'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('apply', 'apply_fill');
			
			$this->params['page'] = Page::seo(['title' => Language::get('apply_fill')]);
        	return $this->render('../apply.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['region_id', 'sgrade', 'cate_id']);
			
			$model = new \frontend\models\ApplyForm(['store_id' => Yii::$app->user->id]);
			if(!($store = $model->save($post, true))) {
				return Message::warning($model->errors);
			}
			
			// 需要审核，跳转到显示审核进度的页面
			if(!$store->state) {
				return Message::display(Language::get('apply_ok'), ['apply/verify']);
         	}
			 
			return Message::display(Language::get('store_opened'), ['store/index', 'id' => $store->store_id]);
		}
	}
	
	/* 店铺审核进度 */
	public function actionVerify()
	{
		if(!($store = StoreModel::find()->select('state,apply_remark')->where(['store_id' => Yii::$app->user->id])->one())) {
			$this->redirect(['apply/index']);
		}
		
		if(in_array($store->state,[Def::STORE_OPEN,Def::STORE_CLOSED])) {
			$this->redirect(['store/index', 'id' => Yii::$app->user->id]);
		}
		
		$this->params['store'] = ArrayHelper::toArray($store);

		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('apply'), Url::toRoute('apply/index'), Language::get('apply_verify'));
			
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('apply', 'apply_verify');
			
		$this->params['page'] = Page::seo(['title' => Language::get('apply_verify')]);
        return $this->render('../apply.verify.html', $this->params);
	}
	
	private function checkApply()
	{
		// 判断是否开启了店铺申请
        if (!Yii::$app->params['store_allow']) {
			$this->errors = Language::get('apply_disabled');
			return false;
        }
		
		// 已有店铺
		if(($store = StoreModel::find()->select('state')->where(['store_id' => Yii::$app->user->id])->one())) {
			if(in_array($store->state, [Def::STORE_OPEN, Def::STORE_CLOSED])) {
				return $this->redirect(['store/index', 'id' => Yii::$app->user->id]);
			}
			if(in_array($store->state, [Def::STORE_APPLYING])) {
				return $this->redirect(['apply/verify']);
			}
			if($store->state == Def::STORE_NOPASS) {
				if($this->action->id != 'fill') {
					return $this->redirect(['apply/fill']);
				}
			}
		}
		
		return true;
	}
}