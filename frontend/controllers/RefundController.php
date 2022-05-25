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

use common\models\RefundModel;
use common\models\RefundMessageModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id RefundController.php 2018.10.17 $
 * @author mosir
 */

class RefundController extends \common\controllers\BaseUserController
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

    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\RefundForm();
		list($recordlist, $page) = $model->formData($post, 20);
		
		$this->params['refundlist'] = $recordlist;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('refund_apply'), Url::toRoute('refund/index'), Language::get('refund_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('refund_apply', 'refund_list');

		$this->params['page'] = Page::seo(['title' => Language::get('refund_apply')]);
        return $this->render('../refund.index.html', $this->params);
	}
	
	public function actionAdd()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\RefundForm();
		list($refund) = $model->getData($get);
		if(!$refund) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['refund'] = $refund;
			$this->params['shippeds'] = $model->getShippedOptions();
			$this->params['reasons'] = $model->getRefundReasonOptions();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('refund_apply'), Url::toRoute('refund/index'), Language::get('refund_add'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('refund_apply', 'refund_add');

			$this->params['page'] = Page::seo(['title' => Language::get('refund_add')]);
        	return $this->render('../refund.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['shipped']);
			
			$model = new \frontend\models\RefundForm();
			if(!($refund = $model->save($post, $get, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refund_apply_ok'), ['refund/view', 'id' => $refund->refund_id]);
		}
	}
	
	public function actionEdit()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\RefundForm();
			
			list($refund) = $model->getData($get);
			if(!$refund) {
				return Message::warning($model->errors);
			}
			$this->params['refund'] = $refund;
			$this->params['shippeds'] = $model->getShippedOptions();
			$this->params['reasons'] = $model->getRefundReasonOptions();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('refund_apply'), Url::toRoute('refund/index'), Language::get('refund_edit'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('refund_apply', 'refund_edit');

			$this->params['page'] = Page::seo(['title' => Language::get('refund_edit')]);
        	return $this->render('../refund.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['shipped']);
			
			$model = new \frontend\models\RefundForm();
			if(!($refund = $model->save($post, $get, true))) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refund_edit_ok'), ['refund/view', 'id' => $refund->refund_id]);
		}
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundViewForm();
		list($refund, $page) = $model->formData($post, 5);
		if(!$refund) {
			return Message::warning($model->errors);
		}
		$this->params['refund'] = $refund;
		$this->params['pagination'] = Page::formatPage($page);
		
		if($refund['seller_id'] == Yii::$app->user->id) {
			$curitem = 'refund_receive';
			$url = Url::toRoute('refund/receive');
		} else {
			$curitem = 'refund_apply';
			$url = Url::toRoute('refund/index');
		}
		
		$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.form.js');
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get($curitem), $url, Language::get('refund_view'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu($curitem, 'refund_view');
		
		$this->params['page'] = Page::seo(['title' => Language::get('refund_view')]);
        return $this->render('../refund.view.html', $this->params);
	}
	
	public function actionMessage()
	{
		if(!Yii::$app->request->isPost)
		{
			return $this->redirect(['refund/index']);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			$model = new \frontend\models\RefundMessageForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('send_message_ok'));	
		}
	}
	
	/* 平台介入处理退款争议 */
	public function actionIntervene()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundInterveneForm();
		if(!$model->save($post, true)) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('intervene_apply_ok'));
	}
	
	/* 取消退款 */ 
	public function actionCancel()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id || !($refund = RefundModel::find()->select('tradeNo')->where(['refund_id' => $post->id, 'buyer_id' => Yii::$app->user->id])->andWhere(['not in', 'status', ['SUCCESS','CLOSED']])->one())) {
			return Message::warning(Language::get('cancel_disallow'));
		}
		
		if(RefundModel::deleteAll(['refund_id' => $post->id])) {
			if(RefundMessageModel::deleteAll(['refund_id' => $post->id])) {
				return Message::display(Language::get('refund_cancel_ok'), ['deposit/record', 'tradeNo' => $refund->tradeNo]);
			}
		}
		return Message::warning(Language::get('refund_cancel_fail'));
	}
	
	// 卖家退款管理
	public function actionReceive()
	{
		Yii::$app->session->set('userRole', 'seller');

		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\RefundForm(['visitor' => 'seller']);
		list($recordlist, $page) = $model->formData($post, 20);
		
		$this->params['refundlist'] = $recordlist;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('refund_receive'), Url::toRoute('refund/receive'), Language::get('refund_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('refund_receive', 'refund_list');

		$this->params['page'] = Page::seo(['title' => Language::get('refund_receive')]);
        return $this->render('../refund.receive.html', $this->params);
	}
	
	/* 卖家同意退款 */
	public function actionAgree()
	{
		Yii::$app->session->set('userRole', 'seller');

		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundAgreeForm();
		if(!$model->submit($post)) {
			return Message::warning($model->errors);
		}
		
		return Message::display(Language::get('seller_agree_refund_ok'), ['refund/view', 'id' => $post->id]);	
	}
	
	/* 卖家拒绝退款 */
	public function actionRefuse()
	{
		Yii::$app->session->set('userRole', 'seller');
		
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		$model = new \frontend\models\RefundRefuseForm();
		if(!($refund = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['refund'] = $refund;
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('refund_receive'), Url::toRoute('refund/receive'), Language::get('refund_refuse'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('refund_receive', 'refund_refuse');

			$this->params['page'] = Page::seo(['title' => Language::get('refund_refuse')]);
			return $this->render('../refund.refuse.html', $this->params);	
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('refuse_ok'), ['refund/view', 'id' => $post->id]);
		}
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus = array(
			array(
				'name' => 'refund_list',
				'url'  => Yii::$app->session->get('userRole') == 'seller' ? Url::toRoute('refund/receive') : Url::toRoute('refund/index')
			)
		);
	
		if(in_array($this->action->id, ['add'])) {
			$submenus[] = array(
				'name'  => 'refund_add',
				'url'	=> '',
			);
		}
		if(in_array($this->action->id, ['edit'])) {
			$submenus[] = array(
				'name'  => 'refund_edit',
				'url'	=> '',
			);
		}
		if(in_array($this->action->id, ['view'])) {
			$submenus[] = array(
				'name'  => 'refund_view',
				'url'	=> '',
			);
		}
		if(in_array($this->action->id, ['refuse'])) {
			$submenus[] = array(
				'name'  => 'refund_refuse',
				'url'	=> '',
			);
		}
        return $submenus;
    }
}