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
use common\library\Page;

/**
 * @Id My_integralController.php 2018.4.17 $
 * @author mosir
 */

class My_integralController extends \common\controllers\BaseUserController
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
		$model = new \frontend\models\My_integralForm();
		if(($integral = $model->formData()) === false) {
			return Message::warning($model->errors);
		}
		$this->params['integral'] = $integral;

		list($integralLogs, $page) = $model->getLogs(null, 20);
		if($integralLogs === false) {
			return Message::warning($model->errors);
		}
		
		$this->params['integrallogs'] = $integralLogs;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_integral'), Url::toRoute('my_integral/index'), Language::get('integral_log'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_integral', 'integral_log');

		$this->params['page'] = Page::seo(['title' => Language::get('integral_log')]);
        return $this->render('../my_integral.index.html', $this->params);
	}
	
	public function actionLogs()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		$curmenu = empty($post->type) ? 'integral_log' : 'integral_'.$post->type;
		
		$model = new \frontend\models\My_integralLogsForm();
		list($integralLogs, $page) = $model->formData($post, 20);
		if($integralLogs === false) {
			return Message::warning($model->errors);
		}
		$this->params['integrallogs'] = $integralLogs;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_integral'), Url::toRoute('my_integral/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_integral', $curmenu);

		$this->params['page'] = Page::seo(['title' => Language::get($curmenu)]);
        return $this->render('../my_integral.logs.html', $this->params);
	}
	
	/* 签到送积分 */
	public function actionSign()
	{
		$model = new \frontend\models\My_integralSignForm();
		if(!($result = $model->submit())) {
			return Message::warning($model->errors);
		}
		list($amount, $signAmount) = $result;
		return Message::result(['amount' => $amount], sprintf(Language::get('signin_integral_successed'), $signAmount));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'integral_log',
                'url'   => Url::toRoute('my_integral/index'),
            ),
            array(
                'name'  => 'integral_income',
                'url'   => Url::toRoute(['my_integral/logs', 'type' => 'income']),
            ),
			array(
                'name'  => 'integral_pay',
                'url'   => Url::toRoute(['my_integral/logs', 'type' => 'pay']),
            ),
            array(
                'name'  => 'integral_frozen',
                'url'   => Url::toRoute(['my_integral/logs', 'type' => 'frozen']),
            ),
        );

        return $submenus;
    }
}