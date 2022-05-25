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

use common\models\UserModel;
use common\models\WebimLogModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Setting;

/**
 * @Id WebimController.php 2018.8.26 $
 * @author mosir
 */

class WebimController extends \common\controllers\BaseAdminController
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
		$post = Basewind::trimAll(Yii::$app->request->get(), true);

		$query = WebimLogModel::find()->alias('wl')->select('wl.logid,wl.add_time, wl.formatContent, wl.fromid, wl.fromName, wl.toid, wl.toName,u.imforbid')->joinWith('fromUser u', false)->orderBy(['logid' => SORT_ASC]);
		$query = $this->getConditions($post, $query);
		
		$page = Page::getPage($query->count(), 20);
		$webimlogs = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		$this->params['webimlogs'] = $webimlogs;
		$this->params['pagination'] = Page::formatPage($page);
		$this->params['filtered'] = $this->getConditions($post);
		
		$this->params['page'] = Page::seo(['title' => Language::get('logs')]);
        return $this->render('../webim.index.html', $this->params);
	}
	
	public function actionSetting()
	{
		if(!Yii::$app->request->isPost) 
		{
			$this->params['setting'] = Setting::getInstance()->getAll();
			
			$this->params['page'] = Page::seo(['title' => Language::get('setting')]);
			return $this->render('../webim.setting.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
		
			$model = new \backend\models\SettingForm();
			if(!$model->save($post, true)) {
				return Message::warning($model->errors);
			}
			return Message::display(Language::get('setting_successed'), ['webim/setting']);
		}
	}
	
	public function actionDeletetalk()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id']);
		if(($model = WebimLogModel::findOne($post->id)) && !$model->delete()) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_ok'));
	}
	
	public function actionChecktalk()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true, ['id', 'imforbid']);
		if($model = UserModel::findOne($post->id)) {
			$model->imforbid = $post->imforbid;
			if(!$model->save()) {
				return Message::warning($model->errors);
			}
		}
		return Message::display($post->imforbid ? Language::get('user_forbid') : Language::get('user_unforbid'));
	}
	
	private function getConditions($post, $query = null)
	{
		if($query === null) {
			foreach(array_keys(ArrayHelper::toArray($post)) as $field) {
				if(in_array($field, ['fromName', 'toName', 'formatContent'])) {
					return true;
				}
			}
			return false;
		}
		if($post->fromName) {
			$query->andWhere(['fromName' => $post->fromName]);
		}
		if($post->toName) {
			$query->andWhere(['toName' => $post->toName]);
		}
		if($post->formatContent) {
			$query->andWhere(['like', 'formatContent',$post->formatContent]);
		}
		return $query;
	}
}
