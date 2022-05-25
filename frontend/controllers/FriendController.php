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

use common\models\FriendModel;
use common\models\UserModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Timezone;
use common\library\Page;

/**
 * @Id FriendController.php 2018.4.18 $
 * @author mosir
 */

class FriendController extends \common\controllers\BaseUserController
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
		$query = FriendModel::find()->alias('f')->select('f.friend_id,u.userid,u.portrait,u.username')->joinWith('userFriend u', false, 'INNER JOIN')->where(['f.userid' => Yii::$app->user->id])->orderBy(['add_time' => SORT_DESC]);
		$page = Page::getPage($query->count(), 20);
		$friends = $query->offset($page->offset)->limit($page->limit)->asArray()->all();

		$this->params['friends'] = $friends;
		$this->params['pagination'] = Page::formatPage($page);
		
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,jquery.plugins/jquery.validate.js,dialog/dialog.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);
			
		// 当前位置
		$this->params['_curlocal'] = Page::setLocal(Language::get('friend'), Url::toRoute('friend/index'), Language::get('friend_index'));
			
		// 当前用户中心菜单
		$this->params['_usermenu'] = Page::setMenu('friend', 'friend_index');
			
		$this->params['page'] = Page::seo(['title' => Language::get('friend_index')]);
        return $this->render('../friend.index.html', $this->params);
	}
	
	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			// 当前位置
			$this->params['_curlocal'] = Page::setLocal(Language::get('deposit'), Url::toRoute('friend/index'), Language::get('friend_add'));
			
			// 当前用户中心菜单
			$this->params['_usermenu'] = Page::setMenu('friend', 'friend_add');

			$this->params['page'] = Page::seo(['title' => Language::get('friend_add')]);
        	return $this->render('../friend.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			$post->username = str_replace(Language::get('comma'), ',', $post->username); //替换中文格式的逗号
            if (!$post->username) {
                return Message::popWarning(Language::get('input_username'));
            }
      
			$users = UserModel::find()->select('userid')->where(['in', 'username', explode(',', $post->username)])->andWhere(['<>', 'userid', Yii::$app->user->id])->indexBy('userid')->asArray()->all();
			
			// 过滤掉已经添加为好友
			$friends = FriendModel::find()->select('friend_id')->where(['userid' => Yii::$app->user->id])->andWhere(['in', 'friend_id', array_keys($users)])->indexBy('friend_id')->asArray()->all();
			$friend_ids = array_diff(array_keys($users), array_keys($friends));// 参数顺序不能变换
			
			if(!$friend_ids) {
				return Message::popWarning(Language::get('no_such_user'));
			}
			
			$model = new FriendModel();
			foreach($friend_ids as $friend_id) {
				$model->isNewRecord = true;
				$model->userid = Yii::$app->user->id;
				$model->friend_id = $friend_id;
				$model->add_time = Timezone::gmtime();
				$model->save(false);
			}
			return Message::popSuccess();
		}
	}
	
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		$friends = FriendModel::find()->select('friend_id')->where(['userid' => Yii::$app->user->id])->andWhere(['in', 'friend_id', explode(',', $post->userid)])->indexBy('friend_id')->all();
		if(!$post->userid || !$friends) {
			return Message::warning(Language::get('no_such_friend'));
		}
		if(!FriendModel::deleteAll(['in', 'friend_id', array_keys($friends)])) {
			return Message::warning(Language::get('drop_fail'));	
		}
		return Message::display(Language::get('drop_ok'), ['friend/index']);
	}
	
	/* 三级菜单 */
    public function getUserSubmenu()
    {
        $submenus =  array(
            array(
                'name'  => 'friend_index',
                'url'   => Url::toRoute('friend/index'),
            ),
		);
        return $submenus;
    }
}