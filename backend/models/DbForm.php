<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace backend\models;

use Yii;
use yii\base\Model;
use yii\helpers\FileHelper;

use common\library\Timezone;

/**
 * @Id DbForm.php 2018.10.30 $
 * @author mosir
 */

class DbForm extends Model
{
	protected $dbdata_path = "sql_backup";
	
	/**
	 *	获取所有数据表
	 */
	public function getTables() {
        $tables = Yii::$app->db->createCommand('SHOW TABLE STATUS')->queryAll();
        return array_map('array_change_key_case', $tables);
	}
 
	/**
	 * 备份地址
	 */
	public function getBackUpPath() {
		$path = Yii::getAlias('@frontend') . '/web/data/' . $this->dbdata_path;
		if(!is_dir($path)) {
			FileHelper::createDirectory($path);
		}
		return $path;
	}
	
	public function checkBackUpName($folder = '') {
		$path = $this->getBackUpPath() . DIRECTORY_SEPARATOR . $folder;
		if(!file_exists($path)){
			return true;
		}else{
			return false;
		}
	}
	
	protected function makeBackUpPath($folder = '') 
	{
		$path = $this->getBackUpPath() . DIRECTORY_SEPARATOR . $folder;
		if(!is_dir($path)) {
			FileHelper::createDirectory($path);
		}
		return $path . DIRECTORY_SEPARATOR;
	}
	
	/**
	 * DIRECTORY_SEPARATOR: 目录分隔符，是定义php的内置常量。在调试机器上，在windows我们习惯性的使用“\”作为文件分隔符，但是在linux上系统不认识这个标识
	 * 在 Windows 中，斜线（/）和反斜线（\）都可以用作目录分隔符，在linux上路径的分隔符是/
	 */
	public function getBackUpConfig($folder = null, $partsize = 100)
	{
		$config = [
            'path'     => $this->makeBackUpPath($folder),
			'lock'	   => realpath(Yii::getAlias('@frontend') . '/web/data/') . DIRECTORY_SEPARATOR . 'backup.lock',
            'part'     => $partsize > 0 ? $partsize * 1024 : 100 * 1024,
            'compress' => 0,// 如果为1，将生成rar压缩包，请不要设置为1
            'level'    => 9,// 压缩级别
        ];
		return $config;
	}
	
	/* 获取所有备份 */
	public function getBackups()
	{
		$path = $this->getBackUpPath() . DIRECTORY_SEPARATOR;
		
		$backups = array(); //所有的备份
		if (is_dir($path))
		{
			if ($handle = opendir($path))
			{
				while (($file = readdir($handle)) !== false)
				{
					if (!in_array($file, ['.', '..']) && filetype($path . $file) == 'dir')
					{
						$backup['name'] = $file;
						$backup['date'] = filemtime($path . $file) - date('Z');
							
						list($vols, $size) = $this->getSqlFiles($path . $file);
						$backup['vols'] = $vols;
						$backup['size'] = $size;
							
						$backups[$backup['date']] = $backup;
					}
				}
			}
		}
		krsort($backups);
			
		return $backups;
	}

	/**
	 * 生成备份名字
	 */
    public function makeBackupName()
    {
        $str = Timezone::localDate('Ymd_', true); //日期前缀
        $No_have_been = array(); //当天已经有的备份序号
		
		$fullpath = $this->getBackUpPath();
		
        if (is_dir($fullpath))
        {
            if ($handle = opendir($fullpath))
            {
                while (($file = readdir($handle)) !== false)
                {
                    if (!in_array($file, ['.', '..']) && filetype($fullpath . DIRECTORY_SEPARATOR . $file) == 'dir')
                    {
                        if (strpos($file, $str) === 0)
                        {
                            $No = intval(str_replace($str, '', $file)); //当天的编号
                            if ($No)
                            {
                                $No_have_been[] = $No;
                            }
                        }
                    }
                }
            }
        }
        if ($No_have_been)
        {
            $str .= max($No_have_been)+1;
        }
        else
        {
			// 没有找到当天备份
            $str .= '1';
        }
        return $str;
    }
	
	/**
	 * 获取备份文件信息 
	 */
    public function getHead($path = null)
    {
        /* 获取sql文件头部信息 */
        $sql_info = array('shopwind_ver' => '', 'mysql_ver' => '', 'php_ver' => 0, 'part' => 0, 'compress' =>  0);
        $fp = fopen($path,'rb');
        $str = fread($fp, 400);
        fclose($fp);
        $arr = explode("\n", $str);
        foreach ($arr as $val)
        {
            $pos = strpos($val, ':');
            if ($pos > 0)
            {
                $type = strtoupper(trim(substr($val, 0, $pos), "-\n\r\t "));
                $value = trim(substr($val, $pos+1), "/\n\r\t ");
              
                if ($type == 'SHOPWIND')
                {
                    $sql_info['shopwind_ver'] = $value;
                }
                elseif ($type == 'MYSQL VERSION')
                {
                    $sql_info['mysql_ver'] = $value;
                }
                elseif ($type == 'PHP VERSION')
                {
                    $sql_info['php_ver'] = $value;
                }
                elseif ($type == 'PART')
                {
                    $sql_info['part'] = intval(substr($value,1));
                }
				elseif ($type == 'COMPRESS')
                {
                    $sql_info['compress'] = intval($value);
                }
            }
        }
        return $sql_info;
    }
 
	/* 获取所有的sql文件 */
	public function getSqlFiles($path = '')
	{
		$sqls = [];
		$size = 0;
		if(file_exists($path)){
			$dir_handle = @opendir($path);
			while($file = @readdir($dir_handle)){
				$file_info = pathinfo($file);
				if($file_info['extension'] == 'sql'){
					$sql['name'] = $file;
					$sql['date'] = filectime($path . '/' . $file)  - date('Z');
					$sql['size'] = ceil(10 * filesize($path . '/' . $file) / 1024) / 10;
					$sqls[] = $sql;
					$size += $sql['size'];
				}
			}
		}
		return array($sqls, $size);
	}
	
	/* 删除目录文件 */
	public function deleteBackup($backup_name)
	{
		$dir = $this->getBackUpPath() . DIRECTORY_SEPARATOR . $backup_name;
		$ret_val = false;
		if (is_dir($dir))
		{
			$d = @dir($dir);
			if($d)
			{
				while (false !== ($entry = $d->read()))
				{
				   if (!in_array($entry, ['.', '..']))
				   {
					   $entry = $dir .'/' . $entry;
					   if(is_dir($entry))
					   {
						   rmdir($entry);
					   }
					   else
					   {
						   @unlink($entry);
					   }
				   }
				}
				$d->close();
				$ret_val = rmdir($dir);
			 }
		}
		else
		{
			$ret_val = unlink($dir);
		}

		return $ret_val;
	}
	
	/* 下载备份文件 */
	public function downloadBackup($backup_name,$file)
	{
		$path = $this->getBackUpPath() . DIRECTORY_SEPARATOR . $backup_name . DIRECTORY_SEPARATOR . $file;
		if(file_exists($path)){
			header('Content-type: application/unknown');
            header('Content-Disposition: attachment; filename="'. $file. '"');
            header("Content-Length: " . filesize($path) ."; ");
            readfile($path);
            exit(0);
		}
		
		return false;
	}
}
