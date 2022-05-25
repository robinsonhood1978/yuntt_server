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

use common\models\ChannelModel;

use common\library\Language;
use common\library\Timezone;

/**
 * @Id ChannelForm.php 2018.9.10 $
 * @author mosir
 */
class ChannelForm extends Model
{
	public $id = 0;
	public $instance = null;
	public $errors = null;
	
	private $clientPath = null;
	private $tpl_filepath = null;
	private $tpl_confpath = null;

	public $template = 'default';
	
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


		$this->tpl_filepath = $this->clientPath . '/views/'.$this->template.'/mall';
		$this->tpl_confpath = $this->clientPath . '/web/data/page_config';
	}
	
	public function valid($post)
	{
		if(empty($post->title)) {
			$this->errors = Language::get('title_empty');
			return false;
		}

		return true;
	}
	
	public function save($post, $valid = true)
	{
		if($valid === true && !$this->valid($post)) {
			return false;
		}

		if(!$this->id || !($model = ChannelModel::find()->where(['cid' => $this->id])->one())) {
			$model = new ChannelModel();
			$model->cid = ChannelModel::genChannelId();
		}
		if($this->id) $style = $model->style;
		
        $model->title = $post->title;
		$model->style = $post->style;
		$model->cate_id = $post->cate_id;
		$model->status = $post->status; 
		$model->add_time = Timezone::gmtime();

		if(!$model->save()) {
			$this->errors = $model->errors;
			return false;
		}
		
		// 如果编辑的时候，修改了风格，则删除原页面文件及配置文件，创建新风格的页面文件及配置文件，注意：先删除再创建
		if($this->id) {
			if($post->style != $style) {
				$this->deleteFile($style, $model->cid);
			}
		}
		
		if(!$this->createFile($model->style, $model->cid)) {
			$model->delete();
			return false;
		}

		return $model->cid;
	}
	
	public function delete()
	{
		if(!$this->id || !($model = ChannelModel::find()->where(['cid' => $this->id])->one())) {
			$this->errors = Language::get('no_such_channel');
			return false;
		}
		
		$model->delete();
		$this->deleteFile($model->style, $model->cid);
		return true;
	}
	
	/* 创建视图（即模板文件）和模板的配置文件 */
	public function createFile($style, $id)
	{
		$tpl_file = $this->tpl_filepath .'/channel.style'.$style.'_'.$id.'.html';
		$tpl_conf = $this->tpl_confpath . '/' . $this->template . '.' . $id . '.config.php';
			
		// 如果文件不存在，则创建
		if(!file_exists($tpl_file))
		{
			if(!($file = $this->getDefaultTplHtml($style))) {
				$this->errors = Language::get('create_file_fail');
				return false;
			}
			$html = file_get_contents($file);
			$html = str_replace("page='channel'", "page='".$id."'", $html);
			
			if(!file_put_contents($tpl_file, $html)) {
				$this->errors = Language::get('create_file_fail');
				return false;
			}
			
		}
		if(!file_exists($tpl_conf))
		{
			if($file = $this->getDefaultTplConf($style)) {		
				$html = file_get_contents($file);
			} else {
				$html = "<?php \n\nreturn array(\n\t'widgets' => array(),\n\t'config' => array()\n);";
			}
			
			if(!file_put_contents($tpl_conf, $html)){
				$this->errors = Language::get('create_conf_fail');
				return false;
			}
		}

		return true;
	}
	
	// 删除视图文件和配置文件
	public function deleteFile($style, $id)
	{
		$tpl_file = $this->tpl_filepath .'/channel.style'.$style.'_'.$id.'.html';
		$tpl_conf = $this->tpl_confpath . '/' . $this->template . '.' . $id . '.config.php';
		
  		if(file_exists($tpl_file)) {
    		unlink($tpl_file);
  		}
		if(file_exists($tpl_conf)) {
    		unlink($tpl_conf);
  		}
	}
	
	// 获取默认的视图文件
	public function getDefaultTplHtml($style = '')
	{
		$file = $this->tpl_filepath . '/channel.style'.$style.'.html';
		if(!$style || !file_exists($file)){
			return '';
		}
		return $file;
	}
	
	// 获取默认的配置文件
	public function getDefaultTplConf($style = '')
	{
		$file = $this->tpl_confpath . '/' . $this->template . '.style' . $style . '.config.php';
		if(!$style || !file_exists($file)){
			return '';
		}
		return $file;
	}
}
