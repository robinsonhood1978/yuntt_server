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

use common\models\GoodsQaModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;

/**
 * @Id My_qaController.php 2018.4.17 $
 * @author mosir
 */

class My_qaController extends \common\controllers\BaseSellerController
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
		$curmenu = in_array($post->type, ['reply', 'replied']) ? $post->type.'_qa' : 'all_qa';
		
		$model = new \frontend\models\My_qaForm(['store_id' => $this->visitor['store_id']]);
		list($questions, $page) = $model->formData($post, 10);

		$this->params['questions'] = $questions;
		$this->params['pagination'] = Page::formatPage($page);
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js',
			'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
		
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('my_qa'), Url::toRoute('my_qa/index'), Language::get($curmenu));
		
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('my_qa', $curmenu);
		
		$this->params['page'] = Page::seo(['title' => Language::get('my_qa')]);
        return $this->render('../my_qa.index.html', $this->params);
	}
	
	/* 回复买家咨询 OR 编辑回复 */
	public function actionReply()
	{
		if(!Yii::$app->request->isPost)
		{
			$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
			
			$question = GoodsQaModel::find()->select('ques_id,question_content,reply_content')->where(['ques_id' => $post->id, 'store_id' => $this->visitor['store_id']])->asArray()->one();
			$this->params['question'] = $question;
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('my_qa'), Url::toRoute('my_qa/index'), Language::get('reply'));
		
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('my_qa', 'reply');
		
			$this->params['page'] = Page::seo(['title' => Language::get('my_qa')]);
        	return $this->render('../my_qa.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['id']);
			
			$model = new \frontend\models\My_qaReplyForm(['store_id' => $this->visitor['store_id']]);
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('reply_successful'));
		}
	}
	
	/* 删除咨询 */
	public function actionDelete()
    {
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		
		if(!$post->id || !is_array(($allId = explode(',', $post->id)))) {
			return Message::warning(Language::get('no_qa_to_drop'));
		}
		
		if(!GoodsQaModel::deleteAll(['and', ['in', 'ques_id', $allId], ['store_id' => $this->visitor['store_id']]])) {
			return Message::warning(Language::get('drop_failed'));
		}
		return Message::display(Language::get('drop_successful'));
    }
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'all_qa',
                'url'   => Url::toRoute(['my_qa/index', 'type' => 'all']),
            ),
            array(
                'name'  => 'reply_qa',
                'url'   => Url::toRoute(['my_qa/index', 'type' => 'reply']),
            ),
			array(
                'name'  => 'replied_qa',
                'url'   => Url::toRoute(['my_qa/index', 'type' => 'replied']),
            ),
        );

        return $submenus;
    }
}