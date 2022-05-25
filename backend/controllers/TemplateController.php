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
use yii\helpers\Url;
use yii\helpers\Json;
use yii\helpers\FileHelper;

use common\models\ChannelModel;
use common\models\IntegralSettingModel;

use common\library\Basewind;
use common\library\Language;
use common\library\Message;
use common\library\Resource;
use common\library\Page;
use common\library\Widget;

/**
 * @Id TemplateController.php 2018.9.5 $
 * @author mosir
 */

class TemplateController extends \common\controllers\BaseAdminController
{
	/**
	 * 初始化
	 */
	public function init()
	{
		parent::init();
	}
	
	public function actionIndex()
	{
		list($client, $template, $page) = $this->getClientParams();
		
		$this->params['client'] = $client;
		$this->params['pages'] = ['pc' => $this->getEditablePages('pc'), 'wap' => $this->getEditablePages('wap')];

		$this->params['_head_tags'] = Resource::import(['style' => 'treetable/treetable.css,dialog/dialog.css']);
		$this->params['_foot_tags'] = Resource::import(['script' => 'jquery.ui/jquery.ui.js,dialog/dialog.js']);
		

		$this->params['page'] = Page::seo(['title' => Language::get('template_diy')]);
		return $this->render('../template.index.html', $this->params);
	}
	
	/* 保存页面 */
    public function actionSave()
    {
		$post = Yii::$app->request->post();
	
        // 初始化变量 页面中所有的挂件 id => name
        $widgets = !empty($post['widgets']) ? $post['widgets'] : array();

        // 页面中所有挂件的位置配置数据
        $config  = !empty($post['config']) ? $post['config'] : array();

        // 当前所编辑的页面
        list($client, $template, $page) = $this->getClientParams();
		
        if (!$page) {
			return Message::warning(Language::get('no_such_page'));
        }
		
        $pages = $this->getEditablePages();
        if (empty($pages[$page])) {
            return Message::warning(Language::get('no_such_page'));
        }
		
		$page_config = Widget::getInstance($client)->getConfig($template, $page);
		
        // 写入位置配置信息
        $page_config['config'] = $config;

        // 原始挂件信息
        $old_widgets = $page_config['widgets'];

        // 清空原始挂件信息
        $page_config['widgets']  = array();

        // 写入挂件信息,指明挂件ID是哪个挂件以及相关配置
        foreach ($widgets as $widget_id => $widget_name)
        {
            // 写入新的挂件信息
            $page_config['widgets'][$widget_id]['name']     = $widget_name;
            $page_config['widgets'][$widget_id]['options']  = array();

            // 如果进行了新的配置，则写入
            if (isset($page_config['tmp'][$widget_id]))
            {
                $page_config['widgets'][$widget_id]['options'] = $page_config['tmp'][$widget_id]['options'];
                continue;
            }

            // 写入旧的配置信息
            $page_config['widgets'][$widget_id]['options'] = $old_widgets[$widget_id]['options'];
        }

        // 清除临时的配置信息
        unset($page_config['tmp']);
		
        // 保存配置
		$this->saveConfig($client, $template, $page, $page_config);
		return Message::result(null, Language::get('publish_successed'));
    }
	
	/* 编辑页面 */
    public function actionEdit()
    {
        // 当前所编辑的页面
        list($client, $template, $page) = $this->getClientParams();
		
        if (!$page) {
			return Message::warning(Language::get('no_such_page'));
        }

        // 注意，通过这种方式获取的页面中跟用户相关的数据都是游客，这样就保证了统一性，所见即所得编辑不会因为您是否已登录而出现不同
        if (!($html = $this->getPageHtml($page))) {
            return Message::warning(Language::get('no_such_page'));
        }
	
		// 给BODY内容加上外标签，以便控制样式
		if($client == 'wap') {
			preg_match("/<body.*?>(.*?)<\/body>/is", $html, $match);
			$html = str_replace($match[0], "<div id='template_page'><div class='ewraper hidden'>".$match[0]."</div></div>", $html);
		}

        // 让页面可编辑，并输出HTML
        echo $this->makeEditable($client, $page, $html);
		exit(0);
    }
	
	/* 获取编辑器面板 */
    public function actionPanel()
    {
		list($client, $template, $page) = $this->getClientParams();
		
		// 获取挂件列表
        $widgets = Widget::getInstance($client)->getList();
		$pages = $this->getEditablePages();
		
		// 将不属于此页面的挂件去除		
		$pageDetail = isset($pages[$page]) ? $pages[$page] : array();	
		$pageKey = (isset($pageDetail['name']) && !empty($pageDetail['name'])) ? $pageDetail['name'] : $page;
		
		// 匹配某个模板某个页面 如：default.index
		$pageKey1 = $template.'.'.$pageKey;
		// 匹配某个模板所有页面 如: default.*
		$pageKey2 = $template.'.*';
	
		foreach($widgets as $key => $widget) {
			if(isset($widget['belongs']) && !empty($widget['belongs'])) {
				$belongs = explode(',', $widget['belongs']);
				if(!in_array($pageKey1, $belongs) && !in_array($pageKey2, $belongs)) {
					unset($widgets[$key]);
				}
			}
		}

        header('Content-Type:text/html;charset=' . Yii::$app->charset);
		$this->params['widgets'] = Json::encode($widgets);
		$this->params['page'] = $this->getPage();

		if($client == 'wap') {
			return $this->render('../template.panel.html', $this->params);
		}
		
		return $this->render('../template.panel.pc.html', $this->params);
    }
	
	/* 配置挂件 */
    public function actionConfig()
    {
		$get = Basewind::trimAll(Yii::$app->request->get(), true);
       
		// 当前所编辑的页面
        list($client, $template, $page) = $this->getClientParams();
		
        if (!Yii::$app->request->isPost) 
		{
			if (!$get->name || !$get->id || !$page) {
            	return Message::warning(Language::get('no_such_widget'));
			}
			
			$page_config = Widget::getInstance($client)->getConfig($template, $page);
        	$options = empty($page_config['tmp'][$get->id]['options']) ? $page_config['widgets'][$get->id]['options'] : $page_config['tmp'][$get->id]['options'];
        	$widget = Widget::getInstance($client)->build($get->id, $get->name, $options);
        	header('Content-Type:text/html;charset=' . Yii::$app->charset);
        	$widget->displayConfig();
        }
		else
		{
			if (!$get->name || !$get->id || !$page) {
				if($client == 'wap') {
					return Message::warning(Language::get('no_such_widget'));
				}
				return $this->configRespond('_d.setTitle("' . Language::get('no_such_widget') . '");_d.setContents("message", {text:"' . Language::get('no_such_widget') . '"});');
			}
			$page_config = Widget::getInstance($client)->getConfig($template, $page);
			$widget = Widget::getInstance($client)->build($get->id, $get->name, $page_config['widgets'][$get->id]['options']);
			
			if(($options = $widget->parseConfig(Yii::$app->request->post())) === false) {
				return Message::warning(Language::get('no_such_widget'));
			}
			$page_config['tmp'][$get->id]['options'] = $options;
				
			// 保存配置信息
			$this->saveConfig($client, $template, $page, $page_config);
			
			// 返回即时更新的数据
			$widget->setOptions($options);
			$contents = $widget->getContents();

			if($client == 'wap') {
				return Message::result($contents, Language::get('save_successed'));
			}
			return $this->configRespond('DialogManager.close("config_dialog");parent.disableLink(parent.$(document.body));parent.$("#' . $get->id . '").replaceWith(document.getElementById("' . $get->id . '").parentNode.innerHTML);parent.init_widget("#' . $get->id . '");', $contents);
		}
    }
	
	/**
	 * 响应配置请求
	 * 这个是弹窗模式下的反馈代码（for PC）
	 */
    public function configRespond($js, $widget = '')
    {
        header('Content-Type:text/html;charset=' . Yii::$app->charset);
        echo  '<div>' . $widget . '</div>' . '<script type="text/javascript">var DialogManager = parent.DialogManager;var _d = DialogManager.get("config_widget");' . $js . '</script>';
    }
	
	/* 保存页面配置文件 */
    public function saveConfig($client = 'pc', $template, $page, $page_config)
    {
        $config_file = Widget::getInstance($client)->getConfigPath($template, $page);

        $php_data = "<?php\n\nreturn " . var_export($page_config, true) . ";";
        return file_put_contents($config_file, $php_data, LOCK_EX);
    }
	
	/* 添加挂件到页面中 */
    public function actionAddwidget()
    {
        $name = Yii::$app->request->get('name', '');
		
        // 当前所编辑的页面
        list($client, $template, $page) = $this->getClientParams();
		
        if (!$name || !$page) {
			return Message::warning(Language::get('no_such_widget'));
        }
		
        $page_config = Widget::getInstance($client)->getConfig($template, $page);
        $id = Widget::getInstance($client)->genUniqueId($page_config);
        $widget = Widget::getInstance($client)->build($id, $name, array());
        $contents = $widget->getContents();
		
		return Message::result(['contents' => $contents, 'widget_id' => $id]);
    }
	
	public function getPage()
    {
        list($client, $template, $page) = $this->getClientParams();
		
        $pages = $this->getEditablePages();
        if(!$pages || !isset($pages[$page]) || empty($pages[$page])) {
        	return false;
        }
        return $pages[$page];
    }
	
	/* 获取欲编辑的页面的HTML */
    public function getPageHtml($page = null)
    {
		$contextOptions = [
			'ssl' => [
				'verify_peer' => false,
				'verify_peer_name' => false
			]
		];

        $pages = $this->getEditablePages();
		foreach($pages as $key => $val){
			if($key == $page){
				//return file_get_contents($val['url']);
				return file_get_contents($val['url'], false, stream_context_create($contextOptions));
			}
		}
		return false;
    }
	
	/* 让页面具有编辑功能 */
    public function makeEditable($client = 'pc', $page, $html)
    {
        $editmode = '<script type="text/javascript" src="' . Url::toRoute(['template/jslang']).'"></script><script type="text/javascript">__PAGE__ = "' . $page . '"; __CLIENT__ ="'.$client.'"; BACK_URL = "' . Basewind::backendUrl() . '";</script>'.Resource::import([
				'script' => 'jquery.ui/jquery.ui.js,jquery.ui/i18n/' . Yii::$app->language . '.js,dialog/dialog.js,layui/layui.js,jquery.plugins/jquery.form.js',
            	'style'=> 'jquery.ui/themes/smoothness/jquery.ui.css,dialog/dialog.css,layui/css/layui.css'
			]).'<script type="text/javascript" src="' . Resource::getThemeAssetsUrl('js/'.($client == 'wap' ? '' : 'pc/').'template_panel.js', false) . '"></script><link href="' .  Resource::getThemeAssetsUrl('css/'.($client == 'wap' ? '' : 'pc/').'template_panel.css', false) .'" rel="stylesheet" type="text/css" />';

        return str_replace('<!--<editmode></editmode>-->', $editmode, $html);
    }
	
	/* 获取可以编辑的页面列表 */
    private function getEditablePages($client = null)
    {
		$data = array();
		
		list($client, $template, $page) = $this->getClientParams($client);
		$siteUrl = $client == 'wap' ? Basewind::mobileUrl() : Basewind::homeUrl();
	
		if(in_array($client, ['pc']))
		{
			$data['index'] = array('title' => Language::get('index'), 'url' => Url::toRoute(['default/index'], $siteUrl), 'action' => array());
			if(IntegralSettingModel::getSysSetting('enabled')) {
				$data['integral'] = array('title' => Language::get('integral_mall'), 'url' => Url::toRoute(['integral/index'], $siteUrl),'action'=>array());
			}
			$data['gcategory'] 	= array('title' => Language::get('gcategory'),'url' => Url::toRoute(['category/index'], $siteUrl), 'action' => array());
			$data['scategory'] 	= array('title' => Language::get('scategory'),'url' => Url::toRoute(['category/store'], $siteUrl), 'action' => array());
			$data['login'] 	= array('title' => Language::get('login'), 'url'=> Url::toRoute(['user/login'], $siteUrl), 'action' => array());
			
			// 频道页
			if(($channels = ChannelModel::find()->indexBy('cid')->all())){
				foreach($channels as $id => $channel){
					$data[$id] = array(
						'title' => $channel->title,
						'url' 	=> Url::toRoute(['channel/index', 'id' => $id], Yii::$app->params['frontendUrl']),
						'action' => array('edit','delete'), 
						'name' 	=> 'channel_style'.$channel->style,
						'status' => $channel->status
					);
				}
			}
		}
		if(in_array($client, ['wap'])) {
			$mobileUrl = Basewind::mobileUrl(false, true);
			$data['index'] = array('title' => Language::get('index'), 'url' => Url::toRoute(['default/index'], $siteUrl), 'path' => $mobileUrl, 'action' => array());
			$data['community'] = array('title' => Language::get('community'), 'url' => Url::toRoute(['community/index'], $siteUrl), 'path' => $mobileUrl ? $mobileUrl . '/pages/community/index/index' : '', 'action' => array());
		}
		
		return $data;
    }
	
	private function getClientParams($client = null)
	{
		$post = Basewind::trimAll(Yii::$app->request->get(), true);
		if($client) $post->client = $client;
	
		$client = (isset($post->client) && in_array($post->client, ['pc', 'wap'])) ? $post->client : 'pc';
		$template = in_array($client, ['wap']) ? Yii::$app->params['wap_template_name'] : Yii::$app->params['template_name'];
		if(!$template) $template = 'default';
		$page = isset($post->page) ? $post->page : 'index';

		return array($client, $template, $page);
	}

	/**
	 * 清除模板编辑上传图片后产生的无效图片
	 * @desc 包含PC及移动端
	 */
	public function actionClearfile()
	{
		// 清除的文件数
		$quantity = 0;

		// 配置文件内容集合
		$contents = '';

		// web目录
		$basePath = Yii::getAlias('@frontend').'/web';
		$baseMPath = Yii::getAlias('@mobile').'/web';

		// PC
		$files = FileHelper::findFiles($basePath.'/data/page_config', ['recursive' => false]);
		foreach($files as $file) {
			$contents .= file_get_contents($file);
		}

		// H5
		$files = FileHelper::findFiles($baseMPath.'/data/page_config', ['recursive' => false]);
		foreach($files as $file) {
			$contents .= file_get_contents($file);
		}

		$preg = '/data\/files\/mall\/template\/[A-Za-z0-9]+(.jpg|.jpeg|.png|.gif|.bmp)/i';
		preg_match_all($preg, $contents, $configImageAll, 0);

		// 模板配置中的所有图片
		if(!isset($configImageAll[0]) || empty($configImageAll[0])) {
			return Message::warning(Language::get('clear_empty'));
		}

		// 模板配置上传过的所有图片
		$folder = $basePath.'/data/files/mall/template';
		if(!is_dir($folder)) FileHelper::createDirectory($folder);
		$uploadImageAll = FileHelper::findFiles($folder, ['recursive' => false]);
		foreach($uploadImageAll as $value) {
			$filePath = str_replace(DIRECTORY_SEPARATOR, '/', str_replace($basePath.'/', '', $value));

			// 如果已上传的图片不在当前配置文件中，说明图片已失效，删除之
			if(!in_array($filePath, $configImageAll[0])) {
				unlink($basePath .'/'. $filePath);
				$quantity++;
			}
		}
		if(!$quantity) {
			return Message::warning(Language::get('clear_empty'));
		}

		return Message::result(null, sprintf(Language::get('clear_ok'), $quantity));
	}
}
