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
use common\library\Resource;
use common\library\Page;
use common\library\Arrayfile;

use backend\library\Database;

/**
 * @Id DbController.php 2018.10.30 $
 * @author mosir
 */

class DbController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}

	/**
	 * 从数据库列表
	 */
	public function actionIndex()
	{
		$config = include(Yii::getAlias('@frontend').'/web/data/config.php');
		$list = isset($config['db']['slaves']) ? $config['db']['slaves'] : [];
		foreach($list as $key => $value) {
			$list[$key] = array_merge($this->dsnToArray($value['dsn']), [
				'username' => $value['username'],
				'password' => $value['password']
			]);
		}
		$this->params['list'] = $list;

		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
		$this->params['_foot_tags'] = Resource::import([
			'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js, dialog/dialog.js',
            'style' =>  'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css'
		]);

		$this->params['page'] = Page::seo(['title' => Language::get('db_slave')]);
		return $this->render('../db.index.html', $this->params);
	}

	/**
	 * 配置/修改从数据库
	 */
	public function actionSlave()
	{
		$key = Yii::$app->request->get('key', 0);

		$file = Yii::getAlias('@frontend').'/web/data/config.php';
		$config = include($file);
		$slaves = isset($config['db']['slaves']) ? (array)$config['db']['slaves'] : [];

		if(!Yii::$app->request->isPost) 
		{
			if($key > 0 && isset($slaves[$key-1])) {
				$value = $slaves[$key-1];
				$slave = array_merge($this->dsnToArray($value['dsn']), [
					'username' => $value['username'],
					'password' => $value['password']
				]);
				$this->params['slave'] = $slave;
			}
			
			$this->params['page'] = Page::seo(['title' => Language::get('db_slave')]);
			return $this->render('../db.slave.html', $this->params);
		}
		else
		{
			$post = Basewind::trimAll(Yii::$app->request->post(), true);

			$slave = [
				'username' => $post->username,
				'password' => $post->password,
				'attributes' => [
					// 使用一个更小的连接超时
					\PDO::ATTR_TIMEOUT => 10,
				],
				'dsn' => "mysql:host={$post->host};port={$post->port};dbname={$post->dbname}"
			];
			if(isset($slaves[$key-1])) {
				$slaves[$key-1] = $slave;
			} else {
				$slaves[] = $slave;
			}
			array_values($slaves);
			$config['db']['slaves'] = $slaves;
			$setting = new Arrayfile();
			$setting->savefile = $file;
			$setting->setAll($config);
			 
			return Message::popSuccess();
		}
	}

	/**
	 * 移除从数据库
	 */
	public function actionRemove()
	{
		$key = Yii::$app->request->get('key', 0);
		if($key <= 0) {
			return Message::warning(Language::get('no_such_db'));
		}

		$file = Yii::getAlias('@frontend').'/web/data/config.php';
		$config = include($file);
		if(!isset($config['db']['slaves']) || !isset($config['db']['slaves'][$key-1])) {
			return Message::warning(Language::get('no_such_db'));
		}
		// 移除
		unset($config['db']['slaves'][$key-1]);
		if(empty($config['db']['slaves'])) {
			unset($config['db']['slaves']);
		} else {
			array_values($config['db']['slaves']);
		}
			
		$setting = new Arrayfile();
		$setting->savefile = $file;
		$setting->setAll($config);

		return Message::display(Language::get('remove_ok'));
	}
	
	/**
	 * 数据备份
	 */
	public function actionBackup()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \backend\models\DbForm();
			$this->params['tables'] = $model->getTables();
			$this->params['backup_name'] = $model->makeBackupName();
			
			$this->params['page'] = Page::seo(['title' => Language::get('db_backup')]);
			return $this->render('../db.backup.html', $this->params);
		}
		else 
		{
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			
			$post = Basewind::trimAll(Yii::$app->request->post());
			
			if(empty($post['tables'])){
				return ['status' => 0, 'info' => Language::get('no_table_selected')];
			}
			
			$model = new \backend\models\DbForm();
			
			if(!$model->checkBackUpName($post['backup_name'])){
				return ['status' => 0, 'info' => Language::get('backup_name_exist')];
			}
			
			$config = $model->getBackUpConfig($post['backup_name'],$post['vol_size']);
			if(is_file($config['lock'])){
				return ['status' => 0, 'info' => Language::get('backup_wait')];
			} else {
				//创建锁文件
				file_put_contents($config['lock'], time());
			}
			//检查备份目录是否可写
			if (!is_writeable($config['path'])) {
				return ['status' => 0, 'info' => Language::get('backup_folder_error')];
			}
			Yii::$app->session->set('backup_config', $config);
	
			//生成备份文件信息
			$file = array(
				'name' => date('Ymd-His', time()),
				'part' => 1,
			);
			Yii::$app->session->set('backup_file', $file);
			
			//缓存要备份的表
			Yii::$app->session->set('backup_tables', $post['tables']);
	
			//创建备份文件
			$database = new Database($file, $config);
			
			if(false !== $database->create()) {
				$tab = ['id' => 0, 'start' => 0];
				return ['status' => 1, 'info' => Language::get('backup_init_ok'), 'tables' => $post['tables'],'tab' => $tab];
			} 
			else  {
				return ['status' => 0, 'info' => Language::get('backup_init_fail')];
			}
		}
	}
	
	/**
	 * 数据导出（即备份）
	 */
	public function actionExport()
    {
        Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
        $tables = Yii::$app->session->get('backup_tables');
        $id = Yii::$app->request->post('id');
        $start = Yii::$app->request->post('start');
		
        // 备份指定表
        $database = new Database(Yii::$app->session->get('backup_file'), Yii::$app->session->get('backup_config'));
        $start  = $database->backup($tables[$id], $start);

        if($start === false) { 
			//出错
            return ['status' => 0, 'info' => Language::get('backup_error')];
        } 
		elseif (0 === $start) 
		{ 
			//下一表
            if(isset($tables[++$id]))
			{
                $tab = array('id' => $id, 'start' => 0);
                return ['status' => 1, 'tab' => $tab,'isnew' => 1];
            } 
			else 
			{ 
				//备份完成，清空缓存
                unlink(Yii::$app->session->get('backup_config')['lock']);
                Yii::$app->session->set('backup_tables', null);
                Yii::$app->session->set('backup_file', null);
                Yii::$app->session->set('backup_config', null);
                
				return ['status' => 1,'info' => Language::get('backup_finished')];
            }
        } 
		else 
		{
            $tab  = array('id' => $id, 'start' => $start[0]);
            $rate = floor(100 * ($start[0] / $start[1]));
            return ['status' => 1, 'info' => Language::get('backuping')."({$rate}%)",'tab' => $tab];
        }
    }
	
	/**
	 * 数据恢复
	 */
	public function actionRecover()
	{
		if(!Yii::$app->request->isPost)
		{
			$model = new \backend\models\DbForm();
			$this->params['backups'] = $model->getBackups();
			
			$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css']);
				
			$this->params['page'] = Page::seo(['title' => Language::get('db_recover')]);
			return $this->render('../db.recover.html', $this->params);
		}
		else
		{
			Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
			
			$model = new \backend\models\DbForm();
			$config = $model->getBackUpConfig(Yii::$app->request->post('name'));
			
			// 获取备份文件信息
			$path  = realpath($config['path']);
			list($files) = $model->getSqlFiles($config['path']);
			
			$list  = array();
			foreach($files as $file) {
				$sql_info = $model->getHead($config['path'].$file['name']);
				$list[$sql_info['part']] = array($sql_info['part'], $config['path'].$file['name'], $sql_info['compress']);
			}
			ksort($list);
		
			//检测文件正确性
			$last = end($list);
			if(count($list) === $last[0]) {
				Yii::$app->session->set('backup_list', $list); //缓存备份列表
				return ['status' => 1,'part' => 1,'start' =>0,'info' => Language::get('recover_init_ok')];
			} 
			else  {
				return ['status' => 0,'info' => Language::get('backup_file_error')];
			}
		}
	}
	
	/**
	 * 导入备份
	 */
	public function actionImport()
    {
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;
		
		$model = new \backend\models\DbForm();
		$config = $model->getBackUpConfig(Yii::$app->request->post('name'));
			
        $part = Yii::$app->request->post('part');
        $start = Yii::$app->request->post('start');

        $list  = Yii::$app->session->get('backup_list');
        $db = new Database($list[$part], array(
            'path'     => realpath($config['path']) . DIRECTORY_SEPARATOR,
            'compress' => $list[$part][2])
		);

        $start = $db->import($start);
        if (false === $start) {
            return ['status' => 0, 'info' => Language::get('recover_fail')];
        } elseif(0 === $start) { //下一卷
            if(isset($list[++$part])){
                return ['status' => 1,'info' => Language::get('recovering')."#{$part}",'part' => $part,'start' => 0];
            } else {
                Yii::$app->session->set('backup_list', null);
                return ['status' => 1, 'info' => Language::get('recover_success')];
            }
        } else {
            if($start[1]){
                $rate = floor(100 * ($start[0] / $start[1]));
                return ['status' => 1, 'info' => Language::get('recovering')."#{$part} ({$rate}%)", 'part' => $part,'start' => $start[0]];
            } else {
                return ['status' => 1,'info' => Language::get('recovering')."#{$part}", 'part' => $part, 'start' => $start[0], 'gz' => 1];
            }
        }
    }
	
	/**
	 * 删除备份
	 */
	public function actionDelete()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(empty($post->backup_name)){
			return Message::warning(Language::get('no_select'));
		}
		$backup_names = explode(',', $post->backup_name);
		foreach ($backup_names as $backup_name)
        {
			$model = new \backend\models\DbForm();
			$model->deleteBackup($backup_name);
        }
		return Message::display(Language::get('drop_ok'));
	}
	
	/**
	 * 下载备份
	 */
	public function actionDownload()
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if(empty($post->file)){
			return Message::warning(Language::get('no_such_file'));
		}
		if(empty($post->backup_name)){
			return Message::warning(Language::get('no_backup_name'));
		}
		$model = new \backend\models\DbForm();
		if(!$model->downloadBackup($post->backup_name,$post->file)){
			return Message::warning(Language::get('no_such_file'));
		}
	}

	/**
	 * 将DSN转为数据格式
	 * @param string $dsn
	 * @example 'dsn' => 'mysql:host=127.0.0.1;port=3306;dbname=shopwind'
	 * @return array array(
	 * 					'engine' => 'mysql', 
	 * 					'host' => '127.0.0.1', 
	 * 					'port' => 3306, 
	 * 					'dbname' => 'shopwind'
	 *				)
	 */
	private function dsnToArray($dsn)
	{
		$dsn = explode(';', $dsn);
		
		$array = [];
		foreach($dsn as $k => $v) {
			$i = explode('=', $v);
			if($k == 0) {
				$a = explode(':', $i[0]);
				$array['engine'] = $a[0];
				$array[$a[1]] = $i[1];
				
				continue;
			}
			$array[$i[0]] = $i[1];
		}
		return $array;
	}
}