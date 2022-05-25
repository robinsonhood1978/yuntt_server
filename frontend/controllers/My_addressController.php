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

use common\models\AddressModel;
use common\models\RegionModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_addressController.php 2018.4.22 $
 * @author mosir
 */

class My_addressController extends \common\controllers\BaseUserController
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
		// 取得列表数据
		$query = AddressModel::find()->where(['userid' => Yii::$app->user->id])->orderBy(['defaddr' => SORT_DESC, 'addr_id' => SORT_DESC]);
		$page = Page::getPage($query->count(), 20);
		$this->params['addressList'] = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['pagination'] = Page::formatPage($page);
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js,mlselection.js',
            'style' => 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
	
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_address'), Url::toRoute('my_address/index'), Language::get('address_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_address', 'address_list');

		$this->params['page'] = Page::seo(['title' => Language::get('address_list')]);
		return $this->render('../my_address.index.html', $this->params);
	}
	
	public function actionAdd()
    {
		if(!Yii::$app->request->isPost)
		{
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0, 'if_show' => 1])->indexBy('region_id')->column();
			$this->params['action'] = Url::toRoute('my_address/add');
			$this->params['redirect'] = Yii::$app->request->get('redirect');

			$this->params['page'] = Page::seo(['title' => Language::get('address_add')]);
			return $this->render('../my_address.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['region_id', 'defaddr']);
			
			$model = new \frontend\models\AddressForm();
			if(!($address = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('address_add_successed'), urldecode(Yii::$app->request->post('redirect', Url::toRoute('my_address/index'))));
		}
	}
	
	public function actionEdit()
    {
		$addr_id = intval(Yii::$app->request->get('addr_id'));
        if(!$addr_id || !($address = AddressModel::find()->where(['addr_id' => $addr_id, 'userid' => Yii::$app->user->id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_address'));
        }
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['regions'] = RegionModel::find()->select('region_name')->where(['parent_id' => 0, 'if_show' => 1])->indexBy('region_id')->column();
			$this->params['address'] = $address;
			$this->params['action'] = Url::toRoute(['my_address/edit', 'addr_id' => $addr_id]);
			$this->params['redirect'] = Yii::$app->request->get('redirect', Url::toRoute('my_address/index'));

			$this->params['page'] = Page::seo(['title' => Language::get('address_edit')]);
			return $this->render('../my_address.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['region_id', 'defaddr']);
			
			$model = new \frontend\models\AddressForm(['addr_id' => $addr_id]);
			if(!($address = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('address_edit_successed'), urldecode(Yii::$app->request->post('redirect', Url::toRoute('my_address/index'))));
		}
	}
	
	public function actionDelete()
    {
        $post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		$model = new \frontend\models\AddressDeleteForm(['addr_id' => $post->addr_id]);
		if(!$model->delete($post, true)) {
			return Message::warning(Language::get('drop_address_failure'));
		}
        return Message::display(Language::get('drop_address_successed'));
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'address_list',
                'url'   => Url::toRoute('my_address/index'),
            ),
        );

        return $submenus;
    }
}