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

use common\models\ChannelModel;
use common\models\GcategoryModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;

/**
 * @Id ChannelController.php 2018.9.10 $
 * @author mosir
 */

class ChannelController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}

	public function actionAdd()
	{
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategories'] = GcategoryModel::getOptions(0, -1, null, 2);
			
			$this->params['page'] = Page::seo(['title' => Language::get('channel_add')]);
			return $this->render('../channel.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'status', 'style']);
			
			$model = new \backend\models\ChannelForm();
			if(!($pageid = $model->save($post, true))) {
				return Message::popWarning($model->errors);
			}

			return Message::popSuccess(Language::get('add_successed'), ['template/edit', 'page' => $pageid]);
		}
	}
	public function actionEdit()
	{
		$id = Yii::$app->request->get('id', 0);
		if(!$id || !($channel = ChannelModel::find()->where(['cid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_channel'));
		}
		
		if(!Yii::$app->request->isPost)
		{
			$this->params['gcategories'] = GcategoryModel::getOptions(0, -1, null, 2);
			$this->params['channel'] = $channel;
			
			$this->params['page'] = Page::seo(['title' => Language::get('channel_edit')]);
			return $this->render('../channel.form.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true, ['cate_id', 'status', 'style']);
			
			$model = new \backend\models\ChannelForm(['id' => $id]);
			if(!$model->save($post, true)) {
				return Message::popWarning($model->errors);
			}
			return Message::popSuccess(Language::get('edit_successed'), ['template/index']);	
		}
	}
	
	public function actionDelete()
	{
		$id = Yii::$app->request->get('id', 0);
		if(!$id || !($channel = ChannelModel::find()->where(['cid' => $id])->asArray()->one())) {
			return Message::warning(Language::get('no_such_channel'));
		}
		
		$model = new \backend\models\ChannelForm(['id' => $id]);
		if(!$model->delete()) {
			return Message::warning($model->errors);
		}
		return Message::display(Language::get('drop_successed'), ['template/index']);
	}
}
