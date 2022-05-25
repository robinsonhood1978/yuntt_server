<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace apiserver\controllers;

use Yii;
use yii\web\Controller;

use common\models\UserModel;
use common\models\OrderIntegralModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;
use common\library\Timezone;
use common\library\Plugin;

use apiserver\library\Respond;
use apiserver\library\Formatter;

/**
 * @Id UserController.php 2018.10.13 $
 * @author yxyc
 */

class UserController extends Controller
{
	public $layout = false;
	public $enableCsrfValidation = false;

	public $params;

	/**
	 * 获取用户信息列表
	 * @api 接口访问地址: http://api.xxx.com/user/list
	 */
	public function actionList()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['page', 'page_size']);

		$query = UserModel::find()->alias('u')
			->select('u.userid,u.username,u.email,u.nickname,u.gender,u.birthday,u.phone_mob,u.im_qq,u.portrait,u.last_login,s.store_id,i.amount as integral,da.money')
			->joinWith('store s', false)
			->joinWith('integral i', false)
			->joinWith('depositAccount da', false);

		$page = Page::getPage($query->count(), $post->page_size, false, $post->page);
		$list = $query->offset($page->offset)->limit($page->limit)->asArray()->all();
		foreach ($list as $key => $value) {
			$list[$key]['portrait'] = Formatter::path($value['portrait'], 'portrait');
			$list[$key]['last_login'] = Timezone::localDate('Y-m-d H:i:s', $value['last_login']);
			$list[$key]['integral'] = floatval($value['integral']);
			$list[$key]['money'] = floatval($value['money']);
		}
		$this->params = ['list' => $list, 'pagination' => Page::formatPage($page, false)];
		return $respond->output(true, Language::get('user_list'), $this->params);
	}

	/**
	 * 获取用户单条信息
	 * @api 接口访问地址: http://api.xxx.com/user/read
	 */
	public function actionRead()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(false)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['userid']);

		$query = UserModel::find()->alias('u')
			->select('u.userid,u.username,u.email,u.nickname,u.gender,u.birthday,u.phone_mob,u.im_qq,u.portrait,u.last_login,s.store_id,i.amount as integral,da.money')
			->joinWith('store s', false)
			->joinWith('integral i', false)
			->joinWith('depositAccount da', false);
		if ($post->userid) $query->where(['u.userid' => $post->userid]);
		elseif ($post->username) $query->where(['u.username' => $post->username]);
		elseif ($post->phone_mob) $query->where(['u.phone_mob' => $post->phone_mob]);
		else $query->where(['u.userid' => Yii::$app->user->id]);

		if (!($record = $query->asArray()->one())) {
			return $respond->output(Respond::USER_NOTEXIST, Language::get('no_such_user'));
		}
		$record['portrait'] = Formatter::path($record['portrait'], 'portrait');
		$record['last_login'] = Timezone::localDate('Y-m-d H:i:s', $record['last_login']);
		$record['integral'] = floatval($record['integral']);
		$record['money'] = floatval($record['money']);

		return $respond->output(true, null, $record);
	}

	/**
	 * 插入用户信息
	 * @api 接口访问地址: http://api.xxx.com/user/add
	 */
	public function actionAdd()
	{
	}

	/**
	 * 更新用户信息
	 * @api 接口访问地址: http://api.xxx.com/user/update
	 */
	public function actionUpdate()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true, ['gender']);

		$model = new \apiserver\models\UserForm(['userid' => Yii::$app->user->id]);
		if (!$model->exists($post)) {
			return $respond->output(Respond::RECORD_NOTEXIST, $model->errors);
		}
		if (!$model->valid($post)) {
			return $respond->output(Respond::PARAMS_INVALID, $model->errors);
		}
		if (!$model->save($post, false)) {
			return $respond->output(Respond::CURD_FAIL, Language::get('user_update_fail'));
		}
		$record = UserModel::find()->select('userid,username,nickname,phone_mob,email,gender,portrait,birthday,im_qq')->where(['userid' => $model->userid])->asArray()->one();

		return $respond->output(true, null, $record);
	}

	/**
	 * 更新用户手机号
	 * @api 接口访问地址: http://api.xxx.com/user/phone
	 */
	public function actionPhone()
	{
		// 验证签名
		$respond = new Respond();
		if (!$respond->verify(true)) {
			return $respond->output(false);
		}

		// 业务参数
		$post = Basewind::trimAll($respond->getParams(), true);

		if(!Basewind::isPhone($post->phone_mob)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_invalid'));
		}
		if(!Basewind::checkPhone($post->phone_mob, Yii::$app->user->id)) {
			return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_mob_existed'));
		}

		// 手机短信验证
		if (($smser = Plugin::getInstance('sms')->autoBuild())) {
			// 兼容微信session不同步问题
			if ($post->verifycodekey) {
				$smser->setSessionByCodekey($post->verifycodekey);
			}
			if (empty($post->verifycode) || (md5($post->phone_mob . $post->verifycode) != Yii::$app->session->get('phone_code'))) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_code_check_failed'));
			}
			if (Timezone::gmtime() - Yii::$app->session->get('last_send_time_phone_code') > 120) {
				return $respond->output(Respond::PARAMS_INVALID, Language::get('phone_code_check_timeout'));
			}
			// 至此，短信验证码是正确的
			UserModel::updateAll(['phone_mob' => $post->phone_mob], ['userid' => Yii::$app->user->id]);
			return $respond->output(true, null, ['userid' => Yii::$app->user->id, 'phone_mob' => $post->phone_mob]);
		}
		return $respond->output(Respond::HANDLE_INVALID, Language::get('handle_exception'));
	}

	/**
	 * 删除用户信息
	 * @api 接口访问地址: http://api.xxx.com/user/delete
	 */
	public function actionDelete()
	{
	}
}
