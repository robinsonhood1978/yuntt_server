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

use common\models\ArticleModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Timezone;
use common\library\Plugin;

/**
 * @Id My_navigationController.php 2018.5.19 $
 * @author mosir
 */

class My_navigationController extends \common\controllers\BaseSellerController
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

		$query = ArticleModel::find()->where(['store_id' => $this->visitor['store_id'], 'cate_id' => Def::STORE_NAV])->orderBy(['sort_order' => SORT_ASC, 'article_id' => SORT_ASC]);
		if($post->title) {
			$query->andWhere(['like', 'title', $post->title]);
			$this->params['filtered'] = true;
		}		
		$page = Page::getPage($query->count(), 15);
		$this->params['navigations'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_navigation'), Url::toRoute('my_navigation/index'), Language::get('navigation_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_navigation', 'navigation_list');

		$this->params['page'] = Page::seo(['title' => Language::get('navigation_list')]);
        return $this->render('../my_navigation.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$navigation = ['if_show' => 1, 'sort_order' => 255];
			$navigation['desc_images'] = UploadedFileModel::find()->select('file_id,file_type,file_name,file_path')->where(['store_id' => $this->visitor['store_id'], 'belong' => Def::BELONG_ARTICLE, 'item_id' => 0])->asArray()->all();
			$this->params['navigation'] = $navigation;
			$this->params['yes_or_no'] = [1 => Language::get('yes'), 0 => Language::get('no')];

			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_ARTICLE,
                'item_id' 		=> 0,
                'upload_url' 	=> Url::toRoute(['upload/add'])
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.validate.js');
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_navigation'), Url::toRoute('my_navigation/index'), Language::get('navigation_add'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_navigation', 'navigation_add');

			$this->params['page'] = Page::seo(['title' => Language::get('navigation_add')]);
        	return $this->render('../my_navigation.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show','sort_order']);
			
			$model = new ArticleModel();
			$model->store_id = $this->visitor['store_id'];
			$model->title = $post->title;
			$model->if_show = $post->if_show;
			$model->sort_order = $post->sort_order;
			$model->description = $post->description;
			$model->cate_id = Def::STORE_NAV;
			$model->add_time = Timezone::gmtime();
			if($model->save() === false) {
				return Message::warning($model->errors);
			}
			
			if($post->file_id) {
				foreach($post->file_id as $file_id) {
					UploadedFileModel::updateAll(['item_id' => $model->article_id], ['file_id' => $file_id]);
				}
			}
			
			return Message::display(Language::get('add_navigation_successed'), ['my_navigation/index']);
		}
	}
	
	public function actionEdit()
	{
		$id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
		{
			$navigation = ArticleModel::find()->where(['store_id' => $this->visitor['store_id'], 'article_id' => $id])->asArray()->one();
			
			$navigation['desc_images'] = UploadedFileModel::find()->select('file_id,file_type,file_name,file_path')->where(['store_id' => $this->visitor['store_id'], 'belong' => Def::BELONG_ARTICLE, 'item_id' => $id])->asArray()->all();
			$this->params['navigation'] = $navigation;
			$this->params['yes_or_no'] = [1 => Language::get('yes'), 0 => Language::get('no')];

			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_ARTICLE,
                'item_id' 		=> $id,
                'upload_url' 	=> Url::toRoute(['upload/add'])
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jquery.validate.js');
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_navigation'), Url::toRoute('my_navigation/index'), Language::get('navigation_edit'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_navigation', 'navigation_edit');

			$this->params['page'] = Page::seo(['title' => Language::get('navigation_edit')]);
        	return $this->render('../my_navigation.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['if_show', 'sort_order']);
			
			$model = ArticleModel::findOne($id);
			$model->title = $post->title;
			$model->if_show = $post->if_show;
			$model->sort_order = $post->sort_order;
			$model->description = $post->description;
			
			if($model->save() === false) {
				return Message::warning($model->errors);
			}
			
			return Message::display(Language::get('edit_navigation_successed'), ['my_navigation/index']);
		}
	}
	
	public function actionDelete()
    {
        $id = Basewind::trimAll(Yii::$app->request->get('id'));
		
		if(!$id) {
			return Message::warning(Language::get('no_such_navigation'));
		}
		
		$articles = ArticleModel::find()->select('article_id')->where(['store_id' => $this->visitor['store_id']])->andWhere(['in', 'article_id', explode(',', $id)])->column();
		
		if($articles && !ArticleModel::deleteAll(['in', 'article_id', $articles])) {
			return Message::warning(Language::get('no_such_navigation'));
		}
        return Message::display(Language::get('drop_navigation_successed'));
    }
	
	public function actionDeleteimage()
	{
		$id = intval(Yii::$app->request->get('id', 0));

		$uploadedfile = UploadedFileModel::find()->alias('f')->select('f.file_id, f.file_path')->where(['f.file_id' => $id, 'store_id' => $this->visitor['store_id']])->asArray()->one();
		if(UploadedFileModel::deleteFileByQuery(array($uploadedfile))) {
			return Message::display($id);
		}
        return Message::warning(Language::get('no_image_droped'));
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name' => 'navigation_list',
                'url'  => Url::toRoute(['my_navigation/index']),
            ),
			array(
                'name' => 'navigation_add',
                'url'  => Url::toRoute(['my_navigation/add']),
            )
        );
		if(in_array($this->action->id, ['edit'])) {
			$submenus[] = array(
				'name' => 'navigation_edit',
				'url' => ''
			);
		}

        return $submenus;
    }
}