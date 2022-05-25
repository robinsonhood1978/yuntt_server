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

use common\models\LimitbuyModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;
use common\library\Promotool;

/**
 * @Id Seller_limitbuyController.php 2018.10.7 $
 * @author mosir
 */

class Seller_limitbuyController extends \common\controllers\BaseSellerController
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
		$limitbuyTool = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']]);
		if(($message = $limitbuyTool->checkAvailable()) !== true) {
			$this->params['tooldisabled'] = $message;
		}
		$this->params['limitbuys'] = $limitbuyTool->getList($params, $page);
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('seller_limitbuy'), Url::toRoute('seller_limitbuy/index'), Language::get('limitbuy_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('seller_limitbuy', 'limitbuy_list');

		$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_list')]);
        return $this->render('../seller_limitbuy.index.html', $this->params);
	}
	
	public function actionAdd()
    {
        if(!Yii::$app->request->isPost)
		{
			$this->params['now'] = Timezone::gmtime();
			
			if(($message = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable()) !== true) {
				$this->params['tooldisabled'] = $message;
			}
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js,jquery.plugins/jquery.form.js,gselector.js,jquery.plugins/timepicker/jquery-ui-timepicker-addon.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css,jquery.plugins/timepicker/jquery-ui-timepicker-addon.css'
			]);
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('seller_limitbuy'), Url::toRoute('seller_limitbuy/index'), Language::get('limitbuy_add'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('seller_limitbuy', 'limitbuy_add');

			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_add')]);
        	return $this->render('../seller_limitbuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('add_limitbuy_ok'), ['seller_limitbuy/index', 'page' => Yii::$app->request->get('ret_page')]);
        }
    }
	
	public function actionEdit()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'ret_page']);
		
		if(!$get->id || !($limitbuy = LimitbuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $get->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_limitbuy'));
		}
		
        if(!Yii::$app->request->isPost)
		{
			$this->params['limitbuy'] = $limitbuy;
			
			if(($message = Promotool::getInstance('limitbuy')->build(['store_id' => $this->visitor['store_id']])->checkAvailable()) !== true) {
				$this->params['tooldisabled'] = $message;
			}
			
			$this->params['_foot_tags'] = Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js,jquery.plugins/jquery.form.js,gselector.js,jquery.plugins/timepicker/jquery-ui-timepicker-addon.js',
            	'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css,jquery.plugins/timepicker/jquery-ui-timepicker-addon.css'
			]);
		
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('seller_limitbuy'), Url::toRoute('seller_limitbuy/index'), Language::get('limitbuy_edit'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('seller_limitbuy', 'limitbuy_edit');

			$this->params['page'] = Page::seo(['title' => Language::get('limitbuy_edit')]);
        	return $this->render('../seller_limitbuy.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id'], 'id' => $get->id]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_limitbuy_ok'), ['seller_limitbuy/index', 'page' => $get->ret_page]);
        }
    }
	
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);

		if(!$post->id) {
			return Message::warning(Language::get('no_such_limitbuy'));
		}
		
		$uploadedfile = LimitbuyModel::find()->select('image')->where(['id' => $post->id, 'store_id' => $this->visitor['store_id']])->andWhere(['!=', 'image', ''])->column();
		
		if(!LimitbuyModel::deleteAll(['id' => $post->id, 'store_id' => $this->visitor['store_id']])) {
			return Message::warning(Language::get('drop_fail'));
		}
		UploadedFileModel::deleteFileByName($uploadedfile);
		
        return Message::display(Language::get('drop_ok'));
    }
	
	public function actionQuery() 
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'toolId']);
		
		if($post->toolId && ($limitbuy = LimitbuyModel::find()->where(['store_id' => $this->visitor['store_id'], 'id' => $post->toolId])->asArray()->one())) {
			$limitbuy['rules'] = unserialize($limitbuy['rules']);
		}
		
		$model = new \frontend\models\Seller_limitbuyForm(['store_id' => $this->visitor['store_id']]);
		return Message::result($model->queryInfo($post->id, $limitbuy));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'limitbuy_list',
                'url'  => Url::toRoute(['seller_limitbuy/index']),
            ),
			array(
                'name' => 'limitbuy_add',
                'url'  => Url::toRoute(['seller_limitbuy/add']),
            )
        );
		if(in_array($this->action->id, ['edit'])) {
			$submenus[] = array(
				'name' => 'limitbuy_edit',
				'url'  => ''
			);
		}

        return $submenus;
    }
}