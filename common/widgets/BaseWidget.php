<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\widgets;

use Yii;
use yii\helpers\ArrayHelper;
use yii\web\UploadedFile;

use common\models\AcategoryModel;
use common\models\GcategoryModel;
use common\models\UploadedFileModel;
use common\models\RecommendModel;
use common\models\RecommendGoodsModel;

use common\library\Basewind;
use common\library\Def;
use common\library\Language;
use common\library\Widget;

/**
 * @Id BaseWidget.php 2018.9.6 $
 * @author mosir
 */
 
class BaseWidget 
{
	var $instance 	= null;
	var $clientPath = null;
	var $id      	= null; //在页面中的唯一标识
	
	var $options = null;    //显示选项
    var $name   = null;     //挂件标识
    var $ttl    = 3600;     //缓存时间
	
	var $params = null;
	var $errors = null;
	
	public function __construct($instance, $clientPath, $id, $options = array())
    {
		$this->BaseWidget($instance, $clientPath, $id, $options);
    }
	public function BaseWidget($instance, $clientPath, $id, $options = array())
    {
        $this->instance = $instance;
		$this->clientPath = $clientPath;
		$this->id = $id;
		
        $this->setOptions($options);
		$this->params['id'] = $this->id;
		$this->params['name'] = $this->name;
    }
	
	/* 设置选项 */
    public function setOptions($options)
    {
        if(!$options) {
            $options = [];
        }
        $this->options = $options;
        $this->params['homeUrl'] = Basewind::homeUrl();
		$this->params['options'] = $this->options;
    }
	
	/* 将取得的数据按模板的样式输出 */
    public function getContents()
    {
        // 获取挂件数据
		$this->params['widget_data'] = array_merge(['uniqueid' => mt_rand()], $this->getData());
		$this->params['options'] = $this->options;
        return $this->wrapContents($this->fetch('widget'));
    }
	
	/* 获取标准的挂件HTML */
    public function wrapContents($html)
    {
        return "\r\n<div id=\"{$this->id}\" name=\"{$this->name}\" widget_type=\"widget\" class=\"widget\">\r\n" . $html . "\r\n</div>\r\n";
    }
	
	/* 获取指定模板的数据 */
    public function fetch($tpl)
    {
        return Yii::$app->controller->renderFile($this->getTpl($tpl), $this->params);
    }
	
	/* 取模板 */
    public function getTpl($tpl)
    {
        return $this->clientPath . '/widgets/' . $this->name . "/{$tpl}.html";
    }
	
	public function display()
    {
        echo $this->getContents();
    }
	
	/* 获取配置表单 */
    public function getConfigForm()
    {
        $this->getConfigDataSrc();
        return $this->fetch('config');
    }

    /* 传递配置页面需要的一些变量 */
    public function getConfigDataSrc()
    {

    }

    /* 显示配置表单 */
    public function displayConfig()
    {
        echo $this->getConfigForm();
		exit();
    }
	
	/* 处理配置项 */
    public function parseConfig($input)
    {
        return $input;
    }
	
	/* 取缓存id */
    public function getCacheId()
    {
        $config = array('widget_name' => $this->name);
        if ($this->options) {
            $config = array_merge($config, $this->options);
        }
        return md5('widget.' . var_export($config, true));
    }
	
	public function getRecommendGoods($recom_id = 0, $num = 10, $default_image = true, $mall_cate_id = 0, $timeslot = false, $sort_by = false, $cached = true)
	{
		return RecommendGoodsModel::getRecommendGoods($recom_id, $num, $default_image, $mall_cate_id, $timeslot, $sort_by, $cached);
	}
	
	public function getGcategoryOptions($store_id = 0, $parent_id = -1, $except = null, $layer = 0, $shown = true, $space = '&nbsp;&nbsp;')
	{
		return GcategoryModel::getOptions($store_id, $parent_id, $except, $layer, $shown, $space);
	}
	public function getAcategoryOptions($store_id = 0, $parent_id = -1, $except = NULL, $layer = 0, $shown = true, $space = '&nbsp;&nbsp;')
	{
		return AcategoryModel::getOptions($store_id, $parent_id, $except, $layer, $shown, $space);
	}
	
	public function getRecommendOptions($catOption = true)
	{
		$recommend = RecommendModel::find()->select('recom_name')->indexBy('recom_id')->column();
		if($catOption) {
			$recommend = ArrayHelper::merge($recommend, array(-100 => Language::get('recommend_new')));
		}
		return $recommend;
	}
	
	/* 挂件上传图片 */
	public function upload($fileVal = '')
	{
		if(!($filePath = UploadedFileModel::getInstance()->upload($fileVal, 0, Def::BELONG_TEMPLATE))) {
			return false;	
		}
		return $filePath;
	}
}