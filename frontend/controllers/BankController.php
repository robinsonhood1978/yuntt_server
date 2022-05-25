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

use common\models\BankModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id BankController.php 2018.4.16 $
 * @author mosir
 */

class BankController extends \common\controllers\BaseUserController
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
		return $this->redirect(['bank/add']);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\BankForm();
			$this->params['bankList'] = $model->getBankList();
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('deposit'), Url::toRoute('deposit/index'), Language::get('bank_add'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('deposit', 'bank_add');
			
			$this->params['page'] = Page::seo(['title' => Language::get('bank_add')]);
        	return $this->render('../bank.form.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\BankForm();
        	if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
				return Message::display(Language::get('add_ok'), ['deposit/index']);
			}
			return Message::warning($model->errors);
		}
	}
	
	public function actionEdit()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \frontend\models\BankForm();
			$this->params['bankList'] = $model->getBankList();
			$this->params['card'] = BankModel::find()->where(['bid' => intval(Yii::$app->request->get('bid'))])->asArray()->one();
			
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('deposit'), Url::toRoute('deposit/index'), Language::get('bank_edit'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('deposit', 'bank_edit');
			
			$this->params['page'] = Page::seo(['title' => Language::get('bank_edit')]);
        	return $this->render('../bank.form.html', $this->params);
		}
		else
		{
			$model = new \frontend\models\BankForm(); //$model->setScenario('update');
        	if ($model->load(Yii::$app->request->post(), '') && $model->save()) {
				return Message::display(Language::get('edit_ok'), ['deposit/index']);
			}
			return Message::warning($model->errors);
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['bid']);
		if(!$post->bid || !($bank = BankModel::find()->where(['userid' => Yii::$app->user->id, 'bid' => $post->bid])->one())) {
			return Message::warning(Language::get('no_such_bank'));
		}
		if($bank->delete() == false) {
			return Message::warning(Language::get('drop_failed'));	
		}
		return Message::display(Language::get('drop_ok'), ['deposit/index']);
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'deposit_index',
                'url'   => Url::toRoute('deposit/index'),
            ),
            array(
                'name'  => 'deposit_config',
                'url'   => Url::toRoute('deposit/config'),
            ),
			array(
                'name'  => 'deposit_tradelist',
                'url'   => Url::toRoute('deposit/tradelist'),
            ),
            array(
                'name'  => 'deposit_recordlist',
                'url'   => Url::toRoute('deposit/recordlist'),
            ),
			array(
                'name'  => 'deposit_indraw',
                'url'   => Url::toRoute('deposit/drawlist'),
            ),
			array(
                'name'  => 'bank_add',
                'url'   => Url::toRoute('bank/add'),
            ),
        );
		if(in_array($this->action->id, ['edit'])) 
		{
			$submenus[] = array(
				'name' => 'bank_edit',
				'url'  => ''
			);
		}

        return $submenus;
    }
}