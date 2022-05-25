<?php

/**
 * @link https://www.shopwind.net/
 * @copyright Copyright (c) 2018 ShopWind Inc. All Rights Reserved.
 *
 * This is not free software. Do not use it for commercial purposes. 
 * If you need commercial operation, please contact us to purchase a license.
 * @license https://www.shopwind.net/license/
 */

namespace common\plugins\oss\qiniucs;

use yii;

use common\plugins\BaseOss;
use common\plugins\oss\qiniucs\SDK;

/**
 * @Id qiniucs.plugin.php 2018.6.5 $
 * @author mosir
 */

class Qiniucs extends BaseOss
{
	/**
     * OSS实例
	 * @var string $code
	 */
    protected $code = 'qiniucs';
    
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
        return $file;
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
     * 列取空间的文件列表
     *
     * @param $prefix     列举前缀
     * @param $marker     列举标识符
     * @param $limit      单次列举个数限制
     * @param $delimiter  指定目录分隔符
     */
    public function listFiles($prefix = null, $marker = null, $limit = 1000, $delimiter = null)
    {
        return $this->getClient()->listFiles($prefix, $marker, $limit, $delimiter);
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

