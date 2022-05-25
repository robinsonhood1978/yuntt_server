<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */
 
namespace common\actions;

use Yii;
use yii\base\Action;

/**
 * @Id JsLangAction.php 2018.3.7 $
 * @author mosir
 */

class JsLangAction extends Action
{
	public $lang = null;
	
	/**
     * Runs the action.
     */
    public function run()
    {
		//header('Content-Encoding:'.Yii::$app->charset);
       	//header("Content-Type: application/x-javascript\n");
        //header("Expires: " .date(DATE_RFC822, strtotime("+1 hour")). "\n");
        if (!$this->lang)
        {
            echo 'var lang = null;';
        }
        else
        {
            echo 'var lang = ' . json_encode($this->lang) . ';';
            echo <<<EOT
				lang.get = function(key){
    				eval('var langKey = lang.' + key);
    				if(typeof(langKey) == 'undefined'){
        				return key;
    				}else{
        				return langKey;
    				}
				}
EOT;
        }
		exit;
	}
}