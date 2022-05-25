<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\oss\aliyuncs;

use yii;

use common\plugins\BaseOss;
use common\plugins\oss\aliyuncs\SDK;

/**
 * @Id aliyuncs.plugin.php 2018.6.5 $
 * @author mosir
 */

class Aliyuncs extends BaseOss
{
	/**
     * OSS实例
	 * @var string $code
	 */
    protected $code = 'aliyuncs';
    
    /**
     * SDK实例
     * @var object $client
     */
    private $client = null;

    /**
	 * 构造函数
	 */
	public function __construct()
	{
		parent::__construct();
	}

    /**
     * 上传文件
     * @param string $fileName
     * @param string $filePath
     */
    public function upload($fileName, $filePath)
    {
        $result = $this->getClient()->upload($fileName, $filePath);
        if(!$result) {
            return false;
        }

        // 返回访问URL地址
        return $this->config['ossUrl'] . '/' . $fileName;
    }

    /**
     * 图片缩放/生成缩微图
     */
    public function thumbnail($file, $width = 400, $height = 400, $background = 'FFFFFF', $mode = 'pad')
    {
        return $this->getClient()->thumbnail($file, $width, $height, $background, $mode);
    }

    /**
     * 删除文件
     * @param $path
     */
    public function delete($path)
    {
        return $this->getClient()->delete($path);
    }

    /**
     * 获取SDK实例
     */
    public function getClient()
    {
        if($this->client === null) {
            $this->client = new SDK($this->config);
        }
        return $this->client;
    }
}

