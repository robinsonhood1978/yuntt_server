<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace frontend\models;

use Yii;
use yii\base\Model; 

use common\models\MessageModel;
use common\library\Timezone;

/**
 * @Id PmSendForm.php 2018.5.21 $
 * @author mosir
 */
class PmSendForm extends Model
{
	public $from_id;
	public $to_id;
	public $title;
	public $content;
	public $add_time;
	public $last_update;
	public $new;
	public $parent_id;
	public $status;
	
	public function __construct($from_id = 0, $to_id = 0, $title = null, $content = null)
	{
		$this->from_id = $from_id;
		$this->to_id = $to_id;
		$this->title = $title;
		$this->content = $content;
		
		parent::init();
	}
	public function send()
	{
		$to_ids = is_array($this->to_id) ? $this->to_id : explode(',', $this->to_id);
       
		foreach ($to_ids as $k => $id)
        {
            if ($this->from_id == $id) {
				
				//不能发给自己
          		continue; 
            }
			$model = new MessageModel();
			$model->from_id = $this->from_id;
			$model->to_id = $id;
			$model->title = $this->title;
			$model->content = $this->content;
			$model->add_time = Timezone::gmtime();
			$model->last_update = Timezone::gmtime();
			$model->status = 3; // 双方未删除
			$model->parent_id = $this->parent_id ? $this->parent_id : 0; // 0：新消息 >1: 回复
			
			if($this->parent_id > 0 && ($query = MessageModel::findOne($this->parent_id))) {
				$model->new = ($this->from_id == $query->from_id) ? 1 : 2; // 如果回复自己发送的主题时
			} else $model->new = 1; // 收件方新消息
			
			$model->save();
        }	
	}
	
	public function sendTo($to_id)
	{
		$this->to_id = $to_id;
		return new self($this->from_id, $this->to_id, $this->title, $this->content);
	}
	
	public function sendFrom($from_id)
	{
		$this->from_id = $this->from_id;
		return new self($this->from_id, $this->to_id, $this->title, $this->content);
	}
}
