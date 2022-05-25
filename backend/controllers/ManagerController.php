<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\controllers;

use Yii;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;
use yii\helpers\Json;

use common\models\UserModel;
use common\models\UserPrivModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Timezone;

/**
 * @Id ManagerController.php 2018.7.31 $
 * @author mosir
 */

class ManagerController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['limit', 'page']);
		
		if(!Yii::$app->request->isAjax) 
		{
			$this->params['filtered'] = $this->getConditions($post);
			$this->params['page'] = Page::seo(['title' => Language::get('admin_list')]);
			return $this->render('../manager.index.html', $this->params);
		}
		else
		{
			$query = UserPrivModel::find()->alias('up')->select('up.privs,up.userid,u.username,u.phone_mob,u.create_time,u.last_login,u.logins,u.last_ip')->joinWith('user u', false)->where(['store_id' => 0]);
			$query = $this->getConditions($post, $query)->orderBy(['userid' => SORT_ASC]);
			
			$page = Page::getPage($query->count(), $post->limit ? $post->limit : 10);
	
			$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
			foreach ($list as $key => $value)
			{
				$list[$key]['create_time']= Timezone::localDate('Y-m-d', $value['create_time']);
				$list[$key]['last_login'] = Timezone::localDate('Y-m-d H:i:s', $value['last_login']);
			}

			return Json::encode(['code' => 0, 'msg' => '', 'count' => $query->count(), 'data' => $list]);
		}
	}
	
	/* 分配管理权限 */
	public function actionAdd()
    {
        $id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
        {
			// 查询用户是存在
			if(!($admin = UserModel::find()->select('username,real_name')->where(['userid' => $id])->asArray()->one())) {
				return Message::warning(Language::get('no_such_user'));
			}
			// 查询是否已是管理员
			if (UserPrivModel::isManager($id)) {
				return Message::warning(Language::get('already_admin'));
			}
			
			$this->params['admin'] = $admin;
			$this->params['privs'] = UserPrivModel::getPrivs();
			
			$this->params['page'] = Page::seo(['title' => Language::get('admin_add')]);
			return $this->render('../manager.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
	
			if(empty($post['privs'])) {
				return Message::warning(Language::get('add_priv'));
			}
            
			if(!($model = UserPrivModel::find()->where(['userid' => $id, 'store_id' => 0])->one())) {
				$model = new UserPrivModel();
				$model->userid = $id;
				$model->store_id = 0;
			}
			$model->privs = implode(',', array_unique($post['privs']));
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('add_ok'), ['manager/index']);
        }
    }
	
	/**
	 * 编辑管理权限
	 * 仅支持单条编辑
	 */
	public function actionEdit()
    {
        $id = intval(Yii::$app->request->get('id'));
		
		if(!Yii::$app->request->isPost)
        {
			// 查询用户是存在
			if(!($admin = UserModel::find()->select('username,real_name')->where(['userid' => $id])->asArray()->one())) {
				return Message::warning(Language::get('no_such_user'));
			}
			// 查询是否已是管理员
			if(!UserPrivModel::isManager($id)) {
				return Message::warning(Language::get('choose_admin'));
			}
			// 判断是否是系统初始管理员
         	if (UserPrivModel::isAdmin($id)) {
				return Message::warning(Language::get('system_admin_edit'));
        	}
			
			$this->params['admin'] = $admin;
			$this->params['privs'] = UserPrivModel::getPrivs($id);
			
			$this->params['page'] = Page::seo(['title' => Language::get('priv_edit')]);
			return $this->render('../manager.form.html', $this->params);
        }
        else
        {
			$post = Basewind::trimAll(Yii::$app->request->post());
	
			if(empty($post['privs'])) {
				return Message::warning(Language::get('add_priv'));
			}
            
			if(!($model = UserPrivModel::find()->where(['userid' => $id, 'store_id' => 0])->one())) {
				$model = new UserPrivModel();
				$model->userid = $id;
				$model->store_id = 0;
			}
			$model->privs = implode(',', array_unique($post['privs']));
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
            return Message::display(Language::get('edit_ok'), ['manager/index']);
        }
    }
	
	/**
	 * 删除管理员（不删除用户）
	 * 支持批量删除
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		if(empty($post->id) || !UserPrivModel::isManager($post->id)) {
			return Message::warning(Language::get('choose_admin'));
		}
		if(UserPrivModel::isAdmin($post->id)) {
			return Message::warning(Language::get('system_admin_drop'));
		}
		if(!UserPrivModel::deleteAll(['and', ['store_id' => 0], ['in', 'userid', $post->id]])) {
			return Message::warning(Language::get('drop_failed'));
		}
		return Message::display(Language::get('drop_ok'));
	}

	/**
	 * 导出数据
	 */
	public function actionExport()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($post->id) $post->id = explode(',', $post->id);
		
		$query = UserPrivModel::find()->alias('up')->select('up.userid,u.username,u.phone_mob,u.im_qq,u.email,u.create_time,u.last_login,u.last_ip,u.logins')
			->joinWith('user u', false)->where(['store_id' => 0])
			->orderBy(['userid' => SORT_ASC]);
		if(!empty($post->id)) {
			$query->andWhere(['in', 'up.userid', $post->id]);
		}
		else {
			$query = $this->getConditions($post, $query)->limit(100);
		}
		if($query->count() == 0) {
			return Message::warning(Language::get('no_data'));
		}

		return \backend\models\ManagerExportForm::download($query->asArray()->all());
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['username', 'real_name', 'email', 'phone_mob'])) {
					return true;
				}
			}
			return false;
		}
		if($post->username) {
			$query->andWhere(['username' => $post->username]);
		}
		if($post->real_name) {
			$query->andWhere(['real_name' => $post->real_name]);
		}
		if($post->email) {
			$query->andWhere(['email' => $post->email]);
		}
		if($post->phone_mob) {
			$query->andWhere(['phone_mob' => $post->phone_mob]);
		}
		return $query;
	}
}
