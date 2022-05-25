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

use common\models\ReportModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id My_reportController.php 2018.4.17 $
 * @author mosir
 */

class My_reportController extends \common\controllers\BaseUserController
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

		$query = ReportModel::find()->alias('r')->select('r.*,g.goods_name,g.default_image,s.store_name')->joinWith('goods g', false)->joinWith('store s', false)->where(['userid' => Yii::$app->user->id])->orderBy(['id' => SORT_DESC]);
		$page = Page::getPage($query->count(), $post->pageper);
		$reports = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		$this->params['reports'] = $reports;
		$this->params['pagination'] = Page::formatPage($page);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_report'), Url::toRoute('my_report/index'), Language::get('report_list'));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_report', 'my_report');
		
		$this->params['page'] = Page::seo(['title' => Language::get('my_report')]);
        return $this->render('../my_report.index.html', $this->params);		
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id){
			return Message::warning(Language::get('no_such_item'));
		}
		
		if(!ReportModel::deleteAll(['and', ['userid' => Yii::$app->user->id], ['in', 'id', explode(',', $post->id)]])) {
			return Message::warning(Language::get('drop_fail'));	
		}
		return Message::display(Language::get('drop_ok'), ['my_report/index']);
	}
	
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'my_report',
                'url'   => Url::toRoute('my_report/index'),
            )
        );
		
		return $submenus;
	}
}