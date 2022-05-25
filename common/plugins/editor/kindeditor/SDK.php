<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\editor\kindeditor;

use yii;

use common\library\Resource;
use common\library\Def;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK
{
	// 表单字段名
	public $name = 'description';
	
	// 编辑器主题
	public $theme = 'default';
	
	// 界面语言
	public $lang = 'zh_CN';
	
	// 是否需要引入JS文件
	public $ext_js = true;
	
	/**
     * @param array $config 插件密钥等配置信息
     */
    public function __construct(array $config)
    {
        foreach($config as $key => $value) {
            $this->$key = $value;
	   }
	}
	
	/**
     * 创建编辑器
	 * @param array $params 编辑器参数集
	 */
	public function create(array $params)
	{
		foreach($params as $key => $value) {
            $this->$key = $value;
		}

		// 主题列表
		$themes = array(
			'default' => "'source','|','undo','redo','|','preview','print','template','plainpaste','wordpaste','|','justifyleft','justifycenter','justifyright','justifyfull','insertorderedlist','insertunorderedlist','indent','outdent','subscript','superscript','clearhtml','quickformat','selectall','|','fullscreen','/','formatblock','fontname','fontsize','|','forecolor','hilitecolor','bold','italic','underline','strikethrough','lineheight','removeformat','|','table','hr','emoticons','baidumap','anchor','link','unlink','|','about'",
			'simple' => "'fontname','fontsize','|','forecolor','hilitecolor','bold','italic','underline','removeformat','|','justifyleft','justifycenter','justifyright','insertorderedlist','insertunorderedlist','|','emoticons','image','link'",
			'mini' => "'fontname','fontsize','|','forecolor','hilitecolor','bold','italic','underline'"
		);
		
        switch ($this->theme)
        {
			case 'mini':
				$theme_config = $themes['mini'];
			break;
            case 'simple':
                $theme_config = $themes['simple'];
            break;
            case 'default':
                $theme_config = $themes['default'];
            break;
            default:
                $theme_config = $themes['default'];
            break;
        }

		if($this->ext_js) {
			$include_js = Resource::import("kindeditor/kindeditor-min.js,kindeditor/lang/{$this->lang}.js");
		}

		$imageJsonArray = json_encode(explode(',', Def::IMAGE_FILE_TYPE));

$str = <<<EOT
$include_js
<script>
	KindEditor.ready(function(K) {
		{$this->name}editor = K.create('textarea[name="{$this->name}"]', {
			themeType : '{$this->theme}',
			items : [$theme_config],
			allowImageUpload : false,
			allowFlashUpload : false,
			allowMediaUpload : false,
			allowFileUpload  : false,
			allowFileManager : false,
			afterBlur: function(){
				this.sync();
			},
			cssData : 'body{font-size:14px; line-height:30px;padding:5px 15px}'
		});
		// 兼容同一个页面存在多个编辑的情况下，插入图片到编辑器的问题
		$('.J_{$this->name}editor').on('click', '*[ectype="insert_editor"]', function() {
			handle_pic = $(this).parents('*[ectype="handle_pic"]');
			file_type = handle_pic.attr("file_type");
			if($.inArray(file_type, {$imageJsonArray}) > -1) {
				html = '<img src="' + handle_pic.attr("file_path") + '" alt="' + handle_pic.attr("file_name") + '">';
			} else {
				html = '<a href="' + handle_pic.attr("file_path") + '" alt="' + handle_pic.attr("file_name") + '">'+handle_pic.attr("file_name")+'</a>';
			}
			{$this->name}editor.insertHtml(html);
		});
	});
</script>

EOT;
        return $str;
    }
}