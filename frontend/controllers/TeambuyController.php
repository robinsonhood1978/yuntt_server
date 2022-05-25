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

use common\models\TeambuyModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Promotool;

/**
 * @Id TeambuyController.php 2019.4.16 $
 * @author mosir
 */

class TeambuyController extends \common\controllers\BaseSellerController
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
		
		if($post->title) {
			$params = ['or', ['like', 'title', $post->title], ['like', 'goods_name', $post->title]];
			$this->params['filtered'] = true;
		}
		
		$page = array('pageSize' => 15);
		$this->params['teambuys'] = TeambuyModel::getList($this->visitor['store_id'], $params, $page);
		$this->params['pagination'] = Page::formatPage($page);
		
		if(($message = Promotool::getInstance('teambuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable()) !== true) {
			$this->params['tooldisabled'] = $message;
		}
	
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('teambuy'), Url::toRoute('teambuy/index'), Language::get('teambuy_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('teambuy', 'teambuy_list');

		$this->params['page'] = Page::seo(['title' => Language::get('teambuy_list')]);
        return $this->render('../teambuy.index.html', $this->params);
	}
	
	public function actionAdd()
    {
        if(!Yii::$app->request->isPost)
		{
			if(($message = Promotool::getInstance('teambuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable()) !== true) {
				$this->params['tooldisabled'] = $message;
			}
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,jquery.plugins/jquery.form.js,gselector.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('teambuy'), Url::toRoute('teambuy/add'), Language::get('teambuy_add'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('teambuy', 'teambuy_add');

			$this->params['page'] = Page::seo(['title' => Language::get('teambuy_add')]);
        	return $this->render('../teambuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['people']);
			
			$model = new \frontend\models\TeambuyForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_ok'), ['teambuy/index']);
        }
    }
	
	public function actionEdit()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'people', 'ret_page']);
		
		if(!$get->id || !($teambuy = TeambuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $get->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_teambuy'));
		}
		
        if(!Yii::$app->request->isPost)
		{
			if(($message = Promotool::getInstance('teambuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable()) !== true) {
				$this->params['tooldisabled'] = $message;
			}
			
			$this->params['teambuy'] = $teambuy;
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,jquery.plugins/jquery.form.js,gselector.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
			]);
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('teambuy'), Url::toRoute('teambuy/index'), Language::get('teambuy_edit'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('teambuy', 'teambuy_edit');

			$this->params['page'] = Page::seo(['title' => Language::get('teambuy_edit')]);
        	return $this->render('../teambuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\TeambuyForm(['store_id' => $this->visitor['store_id'], 'id' => $get->id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'), ['teambuy/index', 'page' => $get->ret_page]);
        }
    }
	
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id) {
			return Message::warning(Language::get('no_such_teambuy'));
		}
		
		if(!TeambuyModel::deleteAll(['id' => $post->id, 'store_id' => $this->visitor['store_id']])) {
			return Message::warning(Language::get('drop_fail'));
		}
		
        return Message::display(Language::get('drop_ok'));
    }
	
	public function actionQuery() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'toolId']);
		
		if($post->toolId && ($teambuy = TeambuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $post->toolId])->asArray()->one())) {
			$teambuy['specs'] = unserialize($teambuy['specs']);
		}
		
		$model = new \frontend\models\TeambuyForm(['store_id' => $this->visitor['store_id']]);
		return Message::result($model->queryInfo($post->id, $teambuy));
	}

	/**
	 * 关闭拼团
	 */
	public function actionClosed()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		
		if(!$post->id) {
			return Message::warning(Language::get('no_such_teambuy'));
		}

		TeambuyModel::updateAll(['status' => 0], ['store_id' => $this->visitor['store_id'], 'id' => $post->id]);
		return Message::result(null, Language::get('handle_ok'));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'teambuy_list',
                'url'  => Url::toRoute(['teambuy/index']),
            ),
			array(
                'name' => 'teambuy_add',
                'url'  => Url::toRoute(['teambuy/add']),
            )
        );
		if(in_array($this->action->id, ['edit'])) {
			$submenus[] = array(
				'name' => 'teambuy_edit',
				'url'  => ''
			);
		}

        return $submenus;
    }
}