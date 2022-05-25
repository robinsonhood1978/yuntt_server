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
use common\library\Page;

/**
 * @Id My_questionController.php 2018.4.17 $
 * @author mosir
 */

class My_questionController extends \common\controllers\BaseUserController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['page']);
		$curmenu = in_array($post->type, ['replied']) ? $post->type.'_qa' : 'all_qa';
		
		$model = new \frontend\models\My_questionForm();
		list($questions, $page) = $model->formData($post, 10);

		$this->params['questions'] = $questions;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_question'), Url::toRoute('my_question/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_question', $curmenu);
		
		$this->params['page'] = Page::seo(['title' => Language::get('my_question')]);
        return $this->render('../my_question.index.html', $this->params);
	}
	
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'all_qa',
                'url'   => Url::toRoute(['my_question/index', 'type' => 'all']),
            ),
            array(
                'name'  => 'replied_qa',
                'url'   => Url::toRoute(['my_question/index', 'type' => 'replied']),
            ),
        );

        return $submenus;
    }
}