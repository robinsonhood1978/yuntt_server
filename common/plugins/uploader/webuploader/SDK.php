<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\uploader\webuploader;

use yii;

use common\library\Language;
use common\library\Resource;
use common\library\Def;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK
{
	// 上传地址
	public $upload_url = null;

	// 实例对象名词，同一个页面需要2个上传实例的，用此区别
	public $obj = 'EDITOR_SWFU';

	// 上传表单选择文件字段名
	public $file_val = 'Filedata';
	
	// 上传文件归属类
	public $belong = 0;
	
	// 上传文件归属项
	public $item_id = 0;

	// 支持的图片格式(为了与模型同步，不应设置该值)
	//public $file_type = Def::IMAGE_FILE_TYPE;

	// 单个上传文件大小(为了与模型同步，不应设置该值)
	//public $file_single_size_limit = Def::IMAGE_FILE_SIZE;
	
	// 最多每次上传文件数
	public $file_num_limit = 50;
	
	// 单次上传文件总大小（40MB）
	public $file_size_limit = 41943040;

	// 是否允许上传文档，如doc|docx|pdf
	public $archived = false;

	// 是否允许多选
	public $multiple = false;
	
	// SWF文件路径，SWF批量上传模式下使用
	public $swf = '';
	
	// 上传按钮文本
	public $button_text = '';
	
	// 上传按钮ID
	public $button_id = 'editor_upload_button';
	
	// 显示上传进度的区域
	public $progress_id = 'editor_upload_progress';
	
	// 是否引入JS文件，同一个页面不用多次引入
	public $ext_js = true;
	
	// 是否引入CSS，同一个页面不用多次引入
	public $ext_css = true;

	// 是否压缩图片（默认最大1600x1600）
	public $compress = true;

	// 表单安全参数
	private $csrfParam;
	
	// 表单安全参数值
	private $csrfToken;
    
    /**
     * @param array $config 插件密钥等配置信息
     */
    public function __construct(array $config)
    {
        foreach($config as $key => $value) {
            $this->$key = $value;
       }

		$this->csrfParam = Yii::$app->request->csrfParam;
		$this->csrfToken = Yii::$app->request->csrfToken;
	}
	
	/**
     * 创建上传组件
	 * @param array $params 上传组件参数集
	 */
	public function create(array $params)
	{
        foreach($params as $key => $value) {
            $this->$key = $value;
		}
	
		$define = $assign = $include_js = $include_css = '';
		if($this->obj) {
			$define = 'var ' . $this->obj . ';';
			$assign = $this->obj . '=';
		}
		
		if(!$this->button_text) {
			$this->button_text = Language::get('uploadedfile');
		}
		
		if($this->ext_js) {
			$include_js = Resource::import('webuploader/webuploader.js,webuploader/js/handlers.js');
		}
		if($this->ext_css) {
			$include_css = Resource::import('webuploader/webuploader.css', 'style');
		}
		if(!$this->swf) {
			$this->swf = Resource::getResourceUrl(['file' => 'webuploader/Uploader.swf']);
		}
		if($this->compress != false) {
			$this->compress = "";
		}

        $str = <<<EOT
{$include_js}
{$include_css}
<script type="text/javascript">
{$define}
$(function(){
    {$assign}WebUploader.create({
    	auto: true,
        server: "{$this->upload_url}",
        swf: "{$this->swf}",
        formData: {
			'{$this->csrfParam}': '{$this->csrfToken}',
            'belong': {$this->belong},
            'item_id': {$this->item_id},
			'fileVal' : "{$this->file_val}",
            'archived' : "{$this->archived}",
            'ajax': 1
        },
        // 只允许选择图片/文件
    	//accept: "",
        // 禁掉全局的拖拽功能。这样不会出现图片拖进页面的时候，把图片打开。
 		disableGlobalDnd: true,
    	fileNumLimit: {$this->file_num_limit},
        fileSizeLimit: {$this->file_size_limit},
        //fileSingleSizeLimit: {$this->file_single_size_limit},
    	duplicate: true,//可以重复上传 但是筛选不了重复图片 根据文件名字、文件大小和最后修改时间来生成hash Key
        fileVal: "{$this->file_val}",
        pick: {
 			id: ".{$this->obj}_filePicker",
   			label: "{$this->button_text}",
            multiple: "{$this->multiple}"
		},
		compress: "{$this->compress}"
    });
    $this->obj.on( 'fileQueued', function( file ) {
    	fileQueued(file, "{$this->progress_id}");
    });
    $this->obj.on( 'uploadProgress', function( file, percentage ) {
    	uploadProgress(file, percentage);
    });
    $this->obj.on( 'uploadSuccess', function( file, response) {
		uploadSuccess(file, response);
	});
    $this->obj.on( 'uploadError', function( file, reason) {
    	uploadError(file, reason);
    });
    $this->obj.on( 'uploadComplete', function( file ) {
    	uploadComplete(file);
    });
    $this->obj.on( 'uploadFinished', function() {
    	uploadFinished("{$this->progress_id}");
	});
	$this->obj.on( 'error', function(type) {
		fileError(type);
    });
});
</script>
EOT;
        return $str;
	}
}