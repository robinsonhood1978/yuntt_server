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
use yii\base\InvalidConfigException;

use OSS\OssClient;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK {

	/**
     * @var string 阿里云OSS AccessKeyID
     */
    public $accessKeyId;

    /**
     * @var string 阿里云OSS AccessKeySecret
     */
    public $accessKeySecret;

    /**
     * @var string 阿里云的bucket空间
     */
    public $bucket;

    /**
     * @var string OSS内网地址, 如:oss-cn-hangzhou-internal.aliyuncs.com
     */
    public $lanDomain;

    /**
     * @var string OSS外网地址, 如:oss-cn-hangzhou.aliyuncs.com
     */
    public $wanDomain;

    /**
     * @var OssClient
     */
    private $_ossClient;

    /**
     * 从lanDomain和wanDomain中选取, 默认走外网
     * @var string 最终操作域名
     */
    protected $baseUrl;

    /**
     * @var bool 是否私有空间, 默认公开空间
     */
    public $isPrivate = false;

    /**
     * @var bool 上传文件是否使用内网，免流量费
     */
    public $isInternal = false;

    public function __construct(array $config)
    {
        foreach($config as $key => $value) {
            $this->$key = $value;
        }

        if ($this->accessKeyId === null) {
            throw new InvalidConfigException('The "accessKeyId" property must be set.');
        } elseif ($this->accessKeySecret === null) {
            throw new InvalidConfigException('The "accessKeySecret" property must be set.');
        } elseif ($this->bucket === null) {
            throw new InvalidConfigException('The "bucket" property must be set.');
        //} elseif ($this->lanDomain === null) {
           // throw new InvalidConfigException('The "lanDomain" property must be set.');
        } elseif ($this->wanDomain === null) {
            throw new InvalidConfigException('The "wanDomain" property must be set.');
        }

        $this->baseUrl = $this->isInternal ? $this->lanDomain : $this->wanDomain;
    }

    /**
     * @return \OSS\OssClient
     * @throws \OSS\Core\OssException
     */
    public function getClient()
    {
        if ($this->_ossClient === null) {
            $this->setClient(new OssClient($this->accessKeyId, $this->accessKeySecret, $this->baseUrl));
        }
        return $this->_ossClient;
    }

    /**
     * @param \OSS\OssClient $ossClient
     */
    public function setClient(OssClient $ossClient)
    {
        $this->_ossClient = $ossClient;
    }

    /**
     * @param $path
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function has($path)
    {
        return $this->getClient()->doesObjectExist($this->bucket, $path);
    }

    /**
     * @param $path
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function read($path)
    {
        if (!($resource = $this->readStream($path))) {
            return false;
        }
        $resource['contents'] = stream_get_contents($resource['stream']);
        fclose($resource['stream']);
        unset($resource['stream']);
        return $resource;
    }

    /**
     * @param $path
     * @return array|bool
     * @throws \OSS\Core\OssException
     */
    public function readStream($path)
    {
        $url = $this->getClient()->signUrl($this->bucket, $path, 3600);
        $stream = fopen($url, 'r');
        if (!$stream) {
            return false;
        }
        return compact('stream', 'path');
    }

    /**
     * @link 访问图片提示下载，参考：https://blog.csdn.net/JGYBZX_G/article/details/111480067
     * @param string $object
     * @param string $file local file path
     * @throws \OSS\Core\OssException
     */
    public function upload($object, $file)
    {
        $mime = getimagesize($file)['mime'];
        if($mime && substr($mime, 0, 6) == 'image/') {
            $options = ['Content-Type' => 'image/jpg'];
        }
   
        return $this->getClient()->uploadFile($this->bucket, $object, $file, $options);
    }
    
    /**
     * 图片缩放/生成缩微图
     * @link https://help.aliyun.com/document_detail/44688.html
     */
    public function thumbnail($file, $width = 400, $height = 400, $background = 'FFFFFF', $mode = 'pad') 
    {
        return $file . "?x-oss-process=image/resize,m_{$mode},h_{$height},w_{$width},color_{$background}";
    }

    /**
     * 删除文件
     * @param $path
     * @return bool
     * @throws \OSS\Core\OssException
     */
    public function delete($path)
    {
        return $this->getClient()->deleteObject($this->bucket, $path) === null;
    }

    /**
     * 创建文件夹
     * @param $dirName
     * @return array|bool
     * @throws \OSS\Core\OssException
     */
    public function createDir($dirName)
    {
        $result = $this->getClient()->createObjectDir($this->bucket, rtrim($dirName, '/'));
        if ($result !== null) {
            return false;
        }
        return ['path' => $dirName];
    }

    /**
     * 获取 Bucket 中所有文件的文件名，返回 Array。
     * @param array $options = [
     *      'max-keys'  => max-keys用于限定此次返回object的最大数，如果不设定，默认为100，max-keys取值不能大于1000。
     *      'prefix'    => 限定返回的object key必须以prefix作为前缀。注意使用prefix查询时，返回的key中仍会包含prefix。
     *      'delimiter' => 是一个用于对Object名字进行分组的字符。所有名字包含指定的前缀且第一次出现delimiter字符之间的object作为一组元素
     *      'marker'    => 用户设定结果从marker之后按字母排序的第一个开始返回。
     * ]
     * @return array
     * @throws \OSS\Core\OssException
     */
    public function getAllObject($options = [])
    {
        $objectListing = $this->getClient()->listObjects($this->bucket, $options);
        $objectKeys = [];
        foreach ($objectListing->getObjectList() as $objectSummary) {
            $objectKeys[] = $objectSummary->getKey();
        }
        return $objectKeys;
    }
}