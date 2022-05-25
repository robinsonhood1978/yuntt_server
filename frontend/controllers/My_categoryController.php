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

use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Tree;

/**
 * @Id My_categoryController.php 2018.5.15 $
 * @author mosir
 */

class My_categoryController extends \common\controllers\BaseSellerController
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
		$tree = new Tree();
		$this->params['gcategories'] = $tree->recursive(false, ['store_id' => $this->visitor['store_id']])->getArrayList(0)->all();

		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.plugins/jquery.validate.js, dialog/dialog.js,jquery.ui/jquery.ui.js',
			'style' => 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_category'), Url::toRoute('my_category/index'), Language::get('gcategory_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_category', 'gcategory_list');

		$this->params['page'] = Page::seo(['title' => Language::get('gcategory_list')]);
        return $this->render('../my_category.index.html', $this->params);
	}
	
	public function actionAdd()
    {
        if (!Yii::$app->request->isPost)
        {
			$post = Basewind::trimAll(Yii::$app->request->get(), true);
            
			$this->params['gcategory'] = ['parent_id' => $post->pid, 'sort_order' => 255, 'if_show' => 1];
			$this->params['parents'] = GcategoryModel::find()->select('cate_name')->where(['store_id' => $this->visitor['store_id'], 'parent_id' => 0])->indexBy('cate_id')->column();
			$this->params['action'] = Url::toRoute(['my_category/add', 'pid' => $post->pid]);
            
            $this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.validate.js');
			
            // 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('my_category'), Url::toRoute('my_category/index'), Language::get('gcategory_add'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('my_category', 'gcategory_add');

			$this->params['page'] = Page::seo(['title' => Language::get('gcategory_add')]);
        	return $this->render('../my_category.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['parent_id','sort_order', 'if_show']);
			
			$model = new GcategoryModel();
			$model->store_id = $this->visitor['store_id'];
			$model->cate_name = $post->cate_name;
			$model->parent_id = $post->parent_id;
			$model->sort_order = $post->sort_order;
			$model->if_show = $post->if_show;
			if($model->save() === false) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess('my_category_add');
		}
    }
	
	public function actionEdit()
    {
		$id = intval(Yii::$app->request->get('id'));
		
        if (!Yii::$app->request->isPost)
        {
			$gcategory = GcategoryModel::find()->where(['store_id' => $this->visitor['store_id'], 'cate_id' => $id])->asArray()->one();
			
			$this->params['gcategory'] = $gcategory;
			$this->params['parents'] = GcategoryModel::find()->select('cate_name')->where(['store_id' => $this->visitor['store_id'], 'parent_id' => 0])->indexBy('cate_id')->column();
			$this->params['action'] = Url::toRoute(['my_category/edit', 'pid' => $gcategory['parent_id'], 'id' => $id]);
            
            $this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.validate.js');
			
            // 当前位置
			//$this->params['_curlocal'] = Page::setLocal(Language::get('my_category'), Url::toRoute('my_category/index'), Language::get('gcategory_edit'));
		
			// 当前用户中心菜单
			//$this->params['_usermenu'] = Page::setMenu('my_category', 'gcategory_edit');
			
			$this->params['page'] = Page::seo(['title' => Language::get('gcategory_edit')]);
        	return $this->render('../my_category.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['parent_id', 'sort_order', 'if_show']);
			
			$model = GcategoryModel::findOne($id);
			if($model->store_id != $this->visitor['store_id']) {
				return Message::popWarning(Language::get('gcategory_empty'));
			}
			$model->cate_name = $post->cate_name;
			$model->parent_id = $post->parent_id;
			$model->sort_order = $post->sort_order;
			$model->if_show = $post->if_show;
			if($model->save() === false) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess('my_category_edit');
		}
    }
	
	public function actionDelete()
	{
		$id = explode(',', Basewind::trimAll(Yii::$app->request->get('id', 0)));
		if(empty($id)) {
			return Message::warning(Language::get('no_gcategory_to_drop'));
		}
		$dropIds = GcategoryModel::find()->select('cate_id')->where(['store_id' => $this->visitor['store_id']])->andWhere(['in', 'cate_id', $id])->column();
		if(!empty($dropIds)) {
			GcategoryModel::deleteAll(['in', 'cate_id', $dropIds]);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'gcategory_list',
                'url'  => Url::toRoute(['my_category/index']),
            )
        );

        return $submenus;
    }
}