<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\library;

use yii;
use yii\web\Response;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

use common\library\Basewind;
use common\library\Language;
use common\library\Page;

/**
 * @Id Message.php 2018.3.6 $
 * @author mosir
 */
 
class Message
{
	/**
	 * 可用于跳转页面提示信息，也可用于JS提交的提示信息 
	 */
    public static function display($message, $redirect = null)
    {
		// 取控制器中的跳转地址
		if(!empty($redirect) && is_array($redirect)) {
			$redirect = Url::toRoute($redirect, true);
		}
		// 取视图中的跳转地址
		elseif($redirect === false) {
			$redirect = '';
		}
		// 不传地址且为null，取当前页面
		elseif($redirect == null) $redirect = Yii::$app->request->getReferrer();
	
		$notice = ['done' => true, 'icon' => 'success', 'msg' => Basewind::getFirstLine($message), 'redirect' => $redirect];
		
		if(Yii::$app->request->isAjax) {
			Yii::$app->response->format = Response::FORMAT_JSON;//此处必须
			return $notice;
		}
		Yii::$app->controller->params['page'] = Page::seo(['title' => Language::get('handle_ok')]);
		return Yii::$app->controller->render('../message.html', ArrayHelper::merge(Yii::$app->controller->params, ['notice' => $notice]));
	}
	
	/**
	 * 可用于跳转页面提示信息，也可用于JS提交的提示信息 
	 */
	public static function warning($message, $redirect = false, $links = [])
	{
		// compatible multiple array
		if($links && (count($links) == count($links, 1))) {
			$links = array($links);
		}
		$notice = ['done' => false, 'icon' => 'warning', 'msg' => Basewind::getFirstLine($message), 'links' => $links];
		if($redirect) {
			$notice['redirect'] = is_array($redirect) ? Url::toRoute($redirect, true) : $redirect;
		}
		
		// 如果是发起弹窗的提示，则直接输入错误
		if(Yii::$app->request->get('dialog_id')) {
			echo sprintf('<p class="padding10 center mb20 gray">'.$notice['msg'].'%s</p>', $redirect ? '<a class="ml10 blue" href="'.$notice['redirect'].'">设置>></a>' : '');
			return;
		}
		
		// 针对AJAX发起的，不需要显示视图
		if(Yii::$app->request->isAjax || Yii::$app->request->get('ajax') || Yii::$app->request->post('ajax')) {
			Yii::$app->response->format = Response::FORMAT_JSON;//此处必须
			return $notice;
		}
		Yii::$app->controller->params['page'] = Page::seo(['title' => Language::get('sys_notice')]);
		return Yii::$app->controller->render('../message.html', ArrayHelper::merge(Yii::$app->controller->params, ['notice' => $notice]));
	}
	
	/**
	 * 用于dialog弹窗失败提交的结果显示 
	 */
	public static function popWarning($message = '')
    {
		if(is_array($message)) 
		{
			$errors = '';
      		foreach($message as $key => $val)
      		{
				$error = '';
				foreach($val as $k => $v) {
        			$error .= $v;
				}
        		$errors .= $errors ? "<br />" . $error : $error;
			}
			$message = $errors;
		}
    	echo "<script type='text/javascript'>window.parent.js_fail('" . $message . "');</script>"; 
    }
	
	/**
	 * 用于dialog弹窗成功提交的结果显示 
	 */
	public static function popSuccess($message = '', $redirect = '', $dialog_id = '')
    {
 		if(empty($dialog_id)) {
       		$dialog_id = strtolower(Yii::$app->controller->id) . '_' . strtolower(Yii::$app->controller->action->id);
		}
   		if (!empty($redirect)) 
		{
			if(is_array($redirect)) $redirect = Url::toRoute($redirect, true);
  			echo "<script type='text/javascript'>window.parent.location.href='".$redirect."';</script>";
		}
		else {
        	echo "<script type='text/javascript'>window.parent.js_success('" . $dialog_id ."');</script>";
		}
    }
	
	/**
	 * 用于JS提交的返回信息，针对于需要返回数据并处理数据的场景 
	 */
	public static function result($retval = '', $message = '', $redirect = '')
    {
		if($redirect !== '') $redirect = Url::toRoute($redirect, true);
		
		Yii::$app->response->format = Response::FORMAT_JSON;
		return array('done' => true, 'msg' => $message, 'redirect' => $redirect, 'retval' => $retval);
    }
}