<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\install\Mysql;

use yii;

use common\library\Timezone;
use common\library\Language;

use common\install\BaseInstall;

/**
 * @Id Mysql.php 2018.10.31 $
 * @author mosir
 */
 
class Mysql5 extends BaseInstall
{
	/**
	 * 数据库实例
	 */
	public $db_type = 'mysql';

	/**
	 * 构造函数
	 * @param string $options
	 */
	public function __construct($options = null)
	{
		if($options !== null && is_array($options)) {
			foreach($options as $key => $value) {
				$this->$key = $value;
			}
		}
	}
	
	/**
	 * 原生sql数据库连接
	 */
	public function connect()
	{
		return $this->sqlconnect();
	}
	
	/**
	 * Yii数据库连接 
	 */
	public function yiiconnect($dsn = null)
	{
		if($dsn == null) {
			$dsn = $this->getDsn();
		}
		return parent::yiiconnect($dsn);
	}
	
	/** 
	 * sql连接 
	 * 另：mysql_connect 自 PHP 5.5.0 起已废弃，并在自 PHP 7.0.0 开始被移除
	 */
	private function sqlconnect($db_name = null)
	{
		// 此处指检查连接是否正常，此时还没有创建数据库
		$link = @mysql_connect($this->db_host . ':' . $this->db_port, $this->db_user, $this->db_password);
        if (!$link) {
			$this->errors = iconv('gbk', 'utf-8', mysql_error()); // 如果不用ICONV，停止MYSQL后的报错字符无法正常输出
			return false;
		}
		if($db_name !== null) mysql_select_db($db_name, $link);

		return $link;
	}
	
	/**
	 * 获取Yii连接数据库的DSN
	 */
	private function getDsn() {
		return 'mysql:host='.$this->db_host.';port='.$this->db_port.';dbname='.$this->db_name;
	}
	
	/* 判断数据库是否存在 */
	public function checkDb()
	{
		if(!$this->db_name) {
			$this->errors = sprintf('database `%s` empty', $this->db_name);
			return false;
		}
		$link = $this->sqlconnect();
		if(!@mysql_select_db($this->db_name, $link)) {
			$this->errors = mysql_error();
			return false;
		}
		return true;
	}
	
	/* 创建数据库 */
	public function createDb()
	{
		$link = $this->sqlconnect();
		$sql = "CREATE DATABASE IF NOT EXISTS `{$this->db_name}` DEFAULT CHARACTER SET " . str_replace('-', '', Yii::$app->charset);
		if(!@mysql_query($sql, $link)) {
			$this->errors = mysql_error();
			return false;
		}
		return true;
	}
	
	/**
	 * 检查表是否存在（避免覆盖表） 
	 * @param bool $force 当有同名数据表的时候，是否强制创建
	 */
	public function checkTable($force = false)
	{
		$link = $this->sqlconnect($this->db_name);
  		$query = mysql_query("SHOW TABLES LIKE '{$this->db_prefix}%'", $link);
            
		$sameTable = false;
     	while($row = mysql_fetch_assoc($query)) {
       		$sameTable = true;
       		break;
     	}

    	// 不同意强制安装，则显示错误
     	if($sameTable && !$force) {
			$this->errors = Language::get('table_existed');
			return false;
		}
		return true;
	}
	
	/* 建立数据表结构 */
	public function createTable()
	{
		parent::showProcess(Language::get('start_setup_db'));
		
		$link = $this->yiiconnect();
		$sqls = parent::getSql(Yii::getAlias('@common') . '/install/versions/structure.sql');
		if(!$sqls) $sqls = array();
		
		// 待表创建成功后，插入系统数据（系统分类和系统文章，包含开店用户协议等文章）
		if($sqls && ($syssql = parent::getSql(Yii::getAlias('@common') .'/install/versions/systemdata.sql'))) {
			foreach($syssql as $sql) {
				$sqls[] = $sql;
			}
		}
		
		foreach ($sqls as $sql)
        {
            $sql = parent::replacePrefix('swd_', $this->db_prefix, $sql);
			if(stripos($sql, 'IF NOT EXISTS ') >= 0) $sql = str_replace('IF NOT EXISTS ', '', $sql);
			
            if(substr($sql, 0, 12) == 'CREATE TABLE') {
                $name = preg_replace("/CREATE TABLE `{$this->db_prefix}([a-z0-9_]+)` .*/is", "\\1", $sql);
				$link->createCommand($this->formatSql($sql, $name))->execute();
                parent::showProcess(sprintf(Language::get('create_table'), $name));
            }
            else
            {
				$link->createCommand($sql)->execute();
            }
        }
	}
	
	/* 安装初始配置 */
	public function saveConfig($post = null, $dsn = null)
	{
		if($dsn == null) {
			$dsn = $this->getDsn();
		}
		return parent::saveConfig($post, $dsn);
	}
	
	/**
	 * 创建网站管理员账号
	 */
	public function createAdmin($post = null)
	{
		$link = $this->yiiconnect();
		
		$admin = $post->admin_name;
		$password = Yii::$app->security->generatePasswordHash($post->admin_pass);
		$sql = "INSERT INTO `{$this->db_prefix}user`(username,password,create_time) VALUES('{$admin}','{$password}',".Timezone::gmtime().")";
		$insertId = $link->createCommand($sql)->execute();
		
		if($insertId > 0) {
			$sql = "REPLACE INTO `{$this->db_prefix}user_priv`(userid,store_id,privs) VALUES({$insertId},0,'all')";
			$link->createCommand($sql)->execute();
		}
	}
	
	/* 有可能SQL文件并没有删除表的语句，所以在创建表的时候，最好先删表再创建表 */
	private function formatSql($sql = '', $name = '') {
    	$type = strtoupper(preg_replace("/^\s*CREATE TABLE\s+.+\s+\(.+?\).*(ENGINE|TYPE)\s*=\s*([a-z]+?).*$/isU", "\\2", $sql));
    	$type = in_array($type, array('MYISAM','HEAP')) ? $type : 'MYISAM';
		
		$dropSql = "DROP TABLE IF EXISTS `{$this->db_prefix}{$name}`;";
		return $dropSql . preg_replace("/^\s*(CREATE TABLE\s+.+\s+\(.+?\)).*$/isU", "\\1", $sql) . " ENGINE={$type} DEFAULT CHARSET=" . str_replace('-', '', Yii::$app->charset);
	}
}