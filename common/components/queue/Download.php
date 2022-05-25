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
 * @Id Download.php 2018.7.4 $
 * @author mosir
 */
 
/*
 * demo for download a image to local
 *
 * step1:
 * cmd run: yii queue/listen
 *
 * step2:
 * in Controller push task
 * Yii::$app->queue->push(new \common\components\queue\Download([
 *		'url' => 'https://www.shopwind.net/data/files/mall/settings/site_logo.png',
 *		'file' => Yii::getAlias('@frontend') . '/image.jpg',
 * ]));
 *
 */
 
class Download extends \yii\base\BaseObject implements \yii\queue\JobInterface
{
    public $url;
    public $file;
    
    public function execute($queue)
    {
        file_put_contents($this->file, file_get_contents($this->url));
    }
}