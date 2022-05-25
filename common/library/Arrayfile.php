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
 * @Id Arrayfile.php 2018.9.6 $
 * @author mosir
 */
 
class Arrayfile
{
	var $filename = null;
	var $savefile = null;
	
	public function __construct() { }
	
	public function getAll()
	{
		if(file_exists($this->filename)) {
			return include($this->filename);
		}
		return array();
	}
	
	public function setAll($setting = array())
	{
		if(!$setting) $setting = array();
		if(!is_array($setting)) $setting = ArrayHelper::toArray($setting);
		
		if(file_exists($this->filename)) {
			$setting = ArrayHelper::merge(include($this->filename), $setting);
		}
		if($this->savefile == null) $this->savefile = $this->filename;
		
		return file_put_contents($this->savefile, "<?php \nreturn " . stripslashes(var_export($setting, true)) . ";");
	}	
}