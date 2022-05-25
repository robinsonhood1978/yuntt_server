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
use common\models\RegionModel;
use common\models\UploadedFileModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Def;
use common\library\Plugin;

/**
 * @Id My_storeController.php 2018.5.17 $
 * @author mosir
 */

class My_storeController extends \common\controllers\BaseSellerController
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
		if(!Yii::$app->request->isPost)
		{
			$store = StoreModel::find()->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			
			// 属于店铺的附件
			$store['desc_images'] = UploadedFileModel::find()->select('file_id,file_type,file_path,file_name')->where(['in', 'item_id', [$this->visitor['store_id']]])->andWhere(['belong' => Def::BELONG_STORE])->orderBy(['file_id' => SORT_ASC])->asArray()->all();
			$this->params['store'] = $store;
			
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0])->indexBy('region_id')->orderBy(['sort_order' => SORT_ASC, 'region_id' => SORT_ASC])->column();
			
			// 编辑器图片批量上传器
			$this->params['build_upload'] = Plugin::getInstance('uploader')->autoBuild(true)->create([
                'belong' 		=> Def::BELONG_STORE,
                'item_id' 		=> $this->visitor['store_id'],
				'upload_url' 	=> Url::toRoute(['upload/add']),
				'compress' 		=> false
			]);
			
			// 所见即所得编辑器
			$this->params['build_editor'] = Plugin::getInstance('editor')->autoBuild(true)->create(['name' => 'description']);
			
			$this->params['_foot_tags'] = Resource::import('jquery.plugins/jscolor.js,webuploader/webuploader.compressupload.js,mlselection.js');
				
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_store'), Url::toRoute('my_store/index'), Language::get('my_store'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_store', 'my_store');

			$this->params['page'] = Page::seo(['title' => Language::get('my_store')]);
			return $this->render('../my_store.index.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['region_id']);
			
			$model = new \frontend\models\My_storeForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('edit_ok'));
		}		
    }
	
	public function actionMap()
	{
		if(!Yii::$app->request->isPost)
		{
			$store = StoreModel::find()->select('longitude,latitude,zoom')->where(['store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['store'] = $store;
			
			$this->params['_foot_tags'] = Resource::import(['remote' => '//api.map.baidu.com/api?v=2.0&ak='.Yii::$app->params['baidukey']['browser']]);
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_store'), Url::toRoute('my_store/index'), Language::get('store_map'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_store', 'store_map');

			$this->params['page'] = Page::seo(['title' => Language::get('store_map')]);
			return $this->render('../my_store.map.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['zoom']);
			
			if(!StoreModel::updateAll(['latitude' => $post->latitude, 'longitude' => $post->longitude, 'zoom' => $post->zoom], ['store_id' => $this->visitor['store_id']])) {
				return Message::warning(Language::get('handle_fail'));
			}
			
			return Message::display(Language::get('handle_ok'));
		}
	}
	
	public function actionSwiper()
	{
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!($store = StoreModel::find()->select('swiper')->where(['store_id' => $this->visitor['store_id']])->asArray()->one())) {
			return Message::warning(Language::get('no_such_store'));
		}
		$store['swiper'] = json_decode($store['swiper'], true);
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['store'] = $store;
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_store'), Url::toRoute('my_store/index'), Language::get('swiper'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_store', 'swiper');

			$this->params['page'] = Page::seo(['title' => Language::get('swiper')]);
			return $this->render('../my_store.swiper.html', $this->params);
		}
		else 
		{
			$post = Basewind::trimAll(Yii::$app->request->post());
			
			for($key = 0; $key < 3; $key++)
			{
				if(($filePath = UploadedFileModel::getInstance()->upload('swiper_url['.$key.']', $this->visitor['store_id'], Def::BELONG_STORE_SWIPER, 0, 'swiper_'.($key+1)))) {
					$store['swiper'][$key]['url'] = $filePath;	
				}
				$store['swiper'][$key]['link'] = $post['swiper_link'][$key];
				if(!isset($store['swiper'][$key]['url']) || empty($store['swiper'][$key]['url'])) {
					unset($store['swiper'][$key]);
				}
			}
			StoreModel::updateAll(['swiper' => json_encode($store['swiper'])], ['store_id' => $this->visitor['store_id']]);
			
			return Message::display(Language::get('edit_ok'), ['my_store/swiper']);
		}
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
	
	/* 异步删除附件 */
    public function actionDeleteswiper()
    {
        $id = intval(Yii::$app->request->get('id', 0));
     
		if(!($store = StoreModel::find()->select('swiper')->where(['store_id' => $this->visitor['store_id']])->asArray()->one())) {
			return Message::warning(Language::get('drop_fail'));
		}
		$store['swiper'] = json_decode($store['swiper'], true);
		foreach($store['swiper'] as $key => $val) {
			if($key == $id) {
				UploadedFileModel::deleteFileByName($val['url']);
				unset($store['swiper'][$key]);
			}
		}
		StoreModel::updateAll(['swiper' => json_encode($store['swiper'])], ['store_id' => $this->visitor['store_id']]);
		return Message::display(Language::get('drop_ok'));
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'my_store',
                'url'   => Url::toRoute('my_store/index'),
            ),
			array(
                'name'  => 'swiper',
                'url'   => Url::toRoute('my_store/swiper'),
            ),
			array(
                'name'  => 'store_map',
                'url'   => Url::toRoute('my_store/map'),
            ),

        );
        return $submenus;
    }
}