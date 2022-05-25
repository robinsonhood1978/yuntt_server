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

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Page;
use common\library\Arrayfile;

/**
 * @Id CacheController.php 2018.9.3 $
 * @author mosir
 */

class CacheController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * 文件缓存配置
	 * @desc 默认缓存类型
	 * @desc 不考虑关闭的情况
	 */
	public function actionIndex()
	{
		if(!Yii::$app->request->isPost) 
		{
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			$this->params['cache'] = $config['cache'];
			
			$this->params['page'] = Page::seo(['title' => Language::get('cache_file')]);
			return $this->render('../cache.file.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			if($post->status) {
				unset($config['redis'], $config['cache']);
				$config = array_merge($config, ['cache' => [
					'class' => 'yii\caching\FileCache',
				 	'cachePath' => '@runtime/cache',
				 ]]);

				$setting = new Arrayfile();
				$setting->savefile = $file;
				$setting->setAll($config);
			} 
		
			return Message::display(Language::get('handle_ok'), ['cache/index']);
		}
	}
	
	/**
	 * Redis缓存配置
	 * @desc 支持本地Redis和云端Redis（推荐阿里云数据库Redis）
	 */
	public function actionRedis()
	{
		if(!Yii::$app->request->isPost) 
		{
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			$this->params['redis'] = $config['cache']['redis'];
			
			$this->params['page'] = Page::seo(['title' => Language::get('cache_redis')]);
			return $this->render('../cache.redis.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			if($post->status) {
				$config = array_merge($config, ['cache' => [
						'class' => 'yii\redis\Cache',
						'redis' => [
							'hostname' => $post->hostname,
							'port' => $post->port,
							'database' => 0,
							'password' => $post->password ? $post->password : null
						],
					],
				]);
			} 
			else 
			{
				unset($config['redis'], $config['cache']);

				// 恢复文件缓存模式
				$config = array_merge($config, ['cache' => [
					'class' => 'yii\caching\FileCache',
				 	'cachePath' => '@runtime/cache',
			 	]]);
			}
			$setting = new Arrayfile();
			$setting->savefile = $file;
			$setting->setAll($config);

			return Message::display(Language::get('handle_ok'), ['cache/redis']);
		}
	}

	/**
	 * Memcache缓存配置
	 * @desc 支持本地Memcache和云端Memcache（推荐阿里云数据库Memcache）
	 */
	public function actionMemcache()
	{
		if(!Yii::$app->request->isPost)
		{
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			$this->params['memcache'] = $config['cache']['servers'];
			
			$this->params['page'] = Page::seo(['title' => Language::get('cache_memcache')]);
			return $this->render('../cache.memcache.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);
			
			$file = Yii::getAlias('@frontend').'/web/data/config.php';
			$config = include($file);
			if($post->status) {
				$config = array_merge($config, ['cache' => [
						'class' => 'yii\caching\MemCache',
						'servers' => [
							array(
								'host' => $post->hostname,
								'port' => $post->port,
								'weight' => 100,
							)							
						],
						'useMemcached' => $post->useMemcached ? true : false
					],
				]);
			} 
			else 
			{
				unset($config['redis'], $config['cache']);

				// 恢复文件缓存模式
				$config = array_merge($config, ['cache' => [
					'class' => 'yii\caching\FileCache',
				 	'cachePath' => '@runtime/cache',
			 	]]);
			}
			$setting = new Arrayfile();
			$setting->savefile = $file;
			$setting->setAll($config);

			return Message::display(Language::get('handle_ok'), ['cache/memcache']);
		}
	}
}
