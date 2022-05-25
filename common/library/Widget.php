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
use yii\helpers\ArrayHelper;

/**
 * @Id Widget.php 2018.9.6 $
 * @author mosir
 */
 
class Widget
{
	var $instance = null;
	var $clientPath = null;
	var $folder = null;
	
	public function __construct($options = null)
	{
		if($options !== null) {
			if(is_string($options)) $options = ['instance' => $options];
			foreach($options as $key => $val) {
				$this->$key = $val;
			}
		}
		if($this->instance == 'wap') {
			$this->clientPath = Yii::getAlias('@mobile');
		} else $this->clientPath = Yii::getAlias('@frontend');
	}
	
	/* 获取挂件基类 */
	public static function getInstance($options = null)
	{
		return new Widget($options);
	}
	
	/* 获取具体挂件实例 */
	public function build($id, $name, $options = array())
	{
		static $widgets = null;
		if (!isset($widgets[$id]))
		{
			$class_path = $this->clientPath . '/widgets/' . $name . '/main.widget.php';
		
			include_once($class_path);
			
			$appId = substr(str_replace(dirname($this->clientPath), '', $this->clientPath), 1);
			$class_name = sprintf("%s\widgets\%s\%sWidget", $appId, $name, ucfirst($name));
			
			$widgets[$id] = new $class_name($this->instance, $this->clientPath, $id, $options);
		}
		return $widgets[$id];
	}
	
	public function getList()
	{
		$widget_dir = $this->clientPath . '/widgets';
        static $widgets = null;
        if ($widgets === null)
        {
            $widgets = array();
            if (!is_dir($widget_dir))
            {
                return $widgets;
            }
            $dir = dir($widget_dir);
            while (false !== ($entry = $dir->read()))
            {
				if (in_array($entry, ['.', '..']) || in_array(substr($entry, 0, 1), ['.', '$'])) {
                    continue;
                }
                $widgets[$entry] = $this->getInfo($entry);
            }
        }
		return $widgets;
	
	}
	public function getInfo($name)
	{
		$file =$this->clientPath . '/widgets/' . $name . '/widget.info.php';
		if(file_exists($file)) {
    		return include($file);
		}
		return array();
	}
	
	/* 获取指定风格，指定页面的挂件的配置信息 */
	public function getConfig($template, $page)
	{
		static $widgets = null;
		$key = $template . '_' . $page;
		if (!isset($widgets[$key]))
		{
			$tmp = array('widgets' => array(), 'config' => array());
			
			$config_file = $this->getConfigPath($template, $page);
			
			if (is_file($config_file)) 
			{
				// 有配置文件，则从配置文件中取
				$tmp = include_once($config_file);
			}
			$widgets[$key] = $tmp;
		}
		return $widgets[$key];
	}

	public function getConfigPath($template, $page) 
	{
		return $this->clientPath . '/web/data/page_config/' . $template . '.' . $page . '.config.php';
	}
	
	public function genUniqueId($page_config)
    {
        $id = '_widget_' . rand(100, 999);
        if (array_key_exists($id, $page_config['widgets'])) {
            return $this->genUniqueId($page_config);
		}        
        return $id;
    }
	
	/* 视图回调函数[显示小挂件] */
    public function displayWidgets($options)
    {
        $area = isset($options['area']) ? $options['area'] : '';
        $page = isset($options['page']) ? $options['page'] : '';
		
        if (!$area || !$page) {
            return;
        }
		
        // 获取该页面的挂件配置信息
		if($this->instance == 'wap') {
       		$widgets = $this->getConfig(Yii::$app->params['wap_template_name'], $page);
		} else $widgets = $this->getConfig(Yii::$app->params['template_name'], $page);
		
        // 如果没有该区域
        if (!isset($widgets['config'][$area])) {
            return;
        }
		
        // 将该区域内的挂件依次显示出来
        foreach ($widgets['config'][$area] as $widget_id)
        {
            $widget_info = $widgets['widgets'][$widget_id];
            $name        = $widget_info['name'];
            $options     = $widget_info['options'];
		
            $widget = $this->build($widget_id, $name, $options); 
            $widget->display();
        }
    }
}