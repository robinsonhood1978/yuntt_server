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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Taskqueue;

/**
 * @Id Buyer_orderController.php 2018.4.17 $
 * @author mosir
 */

class Buyer_orderController extends \common\controllers\BaseUserController
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
		Taskqueue::run();
	}
	
    public function actionIndex()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['evaluation_status']);
		$curmenu = empty($post->type) ? 'all_orders' : $post->type.'_orders';
		
		$model = new \frontend\models\Buyer_orderForm();
		list($orders, $page) = $model->formData($post, 20);
			
		$this->params['orders'] = $orders;
		$this->params['pagination'] = Page::formatPage($page);
		$this->params['filtered'] = $model->getConditions($post);
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js, dialog/dialog.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_order'), Url::toRoute('buyer_order/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_order', $curmenu);
		
		$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
        return $this->render('../buyer_order.index.html', $this->params);
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Buyer_orderViewForm();
		if(!($orderInfo = $model->formData($post))) {
			return Message::warning($model->errors);
		}
		$this->params['order'] = $orderInfo;
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_order'), Url::toRoute('buyer_order/index'), Language::get('order_detail'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_order', 'order_detail');
		
		$this->params['page'] = Page::seo(['title' => Language::get('order_detail')]);
        return $this->render('../buyer_order.view.html', $this->params);
	}
	
	/* 取消订单（没付款之前）*/ 
	public function actionCancel()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Buyer_orderCancelForm();
		if(!($orders = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		if(!Yii::$app->request->isPost)
		{
			$this->params['orders'] = $orders;
			$this->params['order_id'] = implode(',', array_keys($orders));
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_order'), Url::toRoute('buyer_order/index'), Language::get('cancel_order'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_order', 'cancel_order');

			$this->params['page'] = Page::seo(['title' => Language::get('cancel_order')]);
			
			return $this->render('../buyer_order.cancel.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
            if(!$model->submit($post, $orders)) {
				return Message::popWarning($model->errors);
			}
            return Message::popSuccess();
		}
	}
	
	/* 确认订单（买家确认收货）*/
	public function actionConfirm()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Buyer_orderConfirmForm();
		if(!($result = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		list($orderInfo, $tradeInfo) = $result;
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['order'] = $orderInfo;
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_order'), Url::toRoute('buyer_order/index'), Language::get('confirm_order'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_order', 'confirm_order');

			$this->params['page'] = Page::seo(['title' => Language::get('confirm_order')]);
			return $this->render('../buyer_order.confirm.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(!$model->submit($post, $orderInfo, $tradeInfo)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess('ok', ['buyer_order/evaluate', 'order_id' => $orderInfo['order_id']]);
		}
	}
	
	/* 评价订单（给卖家评价）*/
    public function actionEvaluate()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Buyer_orderEvaluateForm();
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
      
        if(!Yii::$app->request->isPost)
        {
            // 获取订单商品 
            $this->params['goods_list'] = $model->getOrderGoods($get);
			$this->params['order'] = $orderInfo;
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/raty/jquery.raty.js');
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_order'), Url::toRoute('buyer_order/index'), Language::get('order_evaluate'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_order', 'order_evaluate');

			$this->params['page'] = Page::seo(['title' => Language::get('order_evaluate')]);
			return $this->render('../buyer_order.evaluate.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
           
			if(!$model->submit($post, $orderInfo)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('evaluate_successed'), ['buyer_order/index']);
        }
    }
	
	/* 买家购物量走势（图表）本月和上月的数据统计 */
	public function actionTrend()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Buyer_orderTrendForm();
		$this->params['echart'] = $model->formData($post);
		
		$this->params['page'] = Page::seo(['title' => Language::get('order_trend')]);
		return $this->render('../echarts.html', $this->params);
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'all_orders',
                'url'   => Url::toRoute('buyer_order/index'),
            ),
			array(
                'name'  => 'pending_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'pending']),
            ),
			array(
                'name'  => 'teaming_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'teaming']),
            ),
			array(
                'name'  => 'accepted_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'accepted']),
            ),
			array(
                'name'  => 'shipped_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'shipped']),
            ),
			array(
                'name'  => 'finished_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'finished']),
            ),
			array(
                'name'  => 'canceled_orders',
                'url'   => Url::toRoute(['buyer_order/index', 'type' => 'canceled']),
            ),
        );
        return $submenus;
    }
}