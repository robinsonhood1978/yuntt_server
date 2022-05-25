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
use common\library\Timezone;
use common\library\Page;
use common\library\Plugin;
use common\library\Taskqueue;

/**
 * @Id Seller_orderController.php 2018.5.16 $
 * @author mosir
 */

class Seller_orderController extends \common\controllers\BaseSellerController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$curmenu = empty($post->type) ? 'all_orders' : $post->type.'_orders';
		
		$model = new \frontend\models\Seller_orderForm(['store_id' => $this->visitor['store_id']]);
		list($orders, $page) = $model->formData($post, 20);
			
		$this->params['orders'] = $orders;
		$this->params['pagination'] = Page::formatPage($page);
		$this->params['filtered'] = $model->getConditions($post);
		$this->params['enable_express'] = Plugin::getInstance('express')->autoBuild();
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js,jquery.plugins/jquery.PrintArea.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('seller_order', $curmenu);

		$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
        return $this->render('../seller_order.index.html', $this->params);
	}
	
	public function actionView()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);

		$model = new \frontend\models\Seller_orderViewForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($post))) {
			return Message::warning($model->errors);
		}
		$this->params['order'] = $orderInfo;
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('order_detail'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('seller_order', 'order_detail');

		$this->params['page'] = Page::seo(['title' => Language::get('order_detail')]);
        return $this->render('../seller_order.view.html', $this->params);
	}
	
	/* 调整费用 */
    public function actionAdjustfee()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Seller_orderAdjustfeeForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;
			
			// 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('adjust_fee'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('seller_order', 'adjust_fee');

			$this->params['page'] = Page::seo(['title' => Language::get('adjust_fee')]);
			return $this->render('../seller_order.adjust_fee.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(!$model->submit($post, $orderInfo)) {
				return Message::popWarning($model->errors);
			}
            return Message::popSuccess();
        }
    }
	
	/* 待发货的订单发货 */
    public function actionShipped()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Seller_orderShippedForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}
		
        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;

			if(($expresser = Plugin::getInstance('express')->autoBuild())) {
				$this->params['express_company'] = $expresser->getCompanys();
			}
			
			// 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('shipped_order'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('seller_order', 'shipped_order');

			$this->params['page'] = Page::seo(['title' => Language::get('shipped_order')]);
			return $this->render('../seller_order.shipped.html', $this->params);
		} 
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			if(!$model->submit($post, $orderInfo)) {
				return Message::popWarning($model->errors);
			}
            return Message::popSuccess();
		}
    }
	
	/* 取消订单（没付款之前） */
	public function actionCancel()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Seller_orderCancelForm(['store_id' => $this->visitor['store_id']]);
		if(!($orders = $model->formData($get))) {
			return Message::warning($model->errors);
		}

		if(!Yii::$app->request->isPost)
		{
			$this->params['orders'] = $orders;
			$this->params['order_id'] = implode(',', array_keys($orders));
			
			// 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('cancel_order'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('seller_order', 'cancel_order');

			$this->params['page'] = Page::seo(['title' => Language::get('cancel_order')]);
			return $this->render('../seller_order.cancel.html', $this->params);
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
	
	/* 卖家给订单添加备忘 */
    public function actionMemo()
    {
        $get = Basewind::trimAll(Yii::$app->request->get(), true, ['order_id']);
		
		$model = new \frontend\models\Seller_orderMemoForm(['store_id' => $this->visitor['store_id']]);
		if(!($orderInfo = $model->formData($get))) {
			return Message::warning($model->errors);
		}

        if (!Yii::$app->request->isPost)
        {
			$this->params['order'] = $orderInfo;
			
			// 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('memo_order'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('seller_order', 'memo_order');

			$this->params['page'] = Page::seo(['title' => Language::get('memo_order')]);
			return $this->render('../seller_order.memo.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['flag']);
            if(!$model->submit($post, $orderInfo)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess();
        }
    }
	
	// 卖家打印订单
	public function actionPrinted()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\Seller_orderPrintedForm(['store_id' => $this->visitor['store_id']]);
		if(!($orders = $model->formData($get))) {
			return Message::warning($model->errors);
		}

        if (!Yii::$app->request->isPost)
        {
			$this->params['orders'] = $orders;
			$this->params['order_id'] = implode(',', array_keys($orders));
			$this->params['now'] = Timezone::gmtime();
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.PrintArea.js');
			
			// 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('seller_order'), Url::toRoute('seller_order/index'), Language::get('printed_order'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('seller_order', 'printed_order');

			$this->params['page'] = Page::seo(['title' => Language::get('printed_order')]);
            return $this->render('../seller_order.printed.html', $this->params);
        }
        else
        {
			return Message::popSuccess();
		}
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'all_orders',
                'url'   => Url::toRoute('seller_order/index'),
            ),
			array(
                'name'  => 'pending_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'pending']),
            ),
			array(
                'name'  => 'teaming_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'teaming']),
            ),
			array(
                'name'  => 'accepted_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'accepted']),
            ),
			array(
                'name'  => 'shipped_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'shipped']),
            ),
			array(
                'name'  => 'finished_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'finished']),
            ),
			array(
                'name'  => 'canceled_orders',
                'url'   => Url::toRoute(['seller_order/index', 'type' => 'canceled']),
            ),
        );
	
        return $submenus;
    }
}