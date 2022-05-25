<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\components\queue;

use yii;

/**
 * @Id SendEmail.php 2018.7.4 $
 * @author mosir
 */
 
class SendEmail extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $compose;
    
    public function execute($queue)
    {
		//echo "\n[begin send email]  to:  " .implode(',',array_keys($this->compose->getTo())). "\n";
        //echo "[begin send email]  subject: ".$this->compose->getSubject()."\n";
        $this->compose->send();
        //echo "[end send email]  \n \n";
    }
}