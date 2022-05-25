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
use yii\base\InvalidConfigException;

use Qiniu\Auth;
use Qiniu\Storage\UploadManager;
use Qiniu\Storage\BucketManager;

/**
 * @Id SDK.php 2018.6.5 $
 * @author mosir
 */

class SDK {

	/**
     * @var string  $accessKey 七牛云OSS accesskey
     */
    public $accessKey;

    /**
     * @var string $secretKey 七牛云OSS secretkey
     */
    public $secretKey;

    /**
     * @var string $bucket 七牛云OSS的bucket空间
     */
    public $bucket;

    /**
     * @var string OSS外网地址
     */
    public $domain;

    /**
     * @var $_ossClient
     */
    private $_ossClient;

    /**
     * @var \Qiniu\auth  $auth
     */
    protected $auth;

    /**
     * @var \Qiniu\Storage\BucketManager $managers 
     */
    protected $managers;

    public function __construct(array $config)
    {
        foreach($config as $key => $value) {
            $this->$key = $value;
        }

        if ($this->accessKey === null) {
            throw new InvalidConfigException('The "accessKey" property must be set.');
        } elseif ($this->secretKey === null) {
            throw new InvalidConfigException('The "secretKey" property must be set.');
        } elseif ($this->bucket === null) {
            throw new InvalidConfigException('The "bucket" property must be set.');
        } elseif ($this->domain === null) {
            throw new InvalidConfigException('The "domain" property must be set.');
        }

        $this->auth = new Auth($this->accessKey, $this->secretKey);
        $this->managers = [];
    }

    /**
     * @return \Qiniu\Storage\UploadManager
     */
    public function getClient()
    {
        if ($this->_ossClient === null) {
            $this->setClient(new UploadManager());
        }
        return $this->_ossClient;
    }

    /**
     * @return \Qiniu\Storage\UploadManager
     */
    public function setClient(UploadManager $ossClient)
    {
        $this->_ossClient = $ossClient;
    }

    /**
     * 使用文件内容上传
     * @param string $fileName 目标文件名
     * @param string $fileData 文件内容
     * @return mixed
     */
    public function put($fileName, $fileData)
    {
        $uploadToken = $this->auth->uploadToken($this->bucket);
        list($ret, $error) = $this->getClient()->put($uploadToken, $fileName, $fileData);
        if(is_null($error)) {
            return true;
        }
        return false;
    }

    /**
     * 使用文件路径上传
     * @param string $fileName 目标文件名
     * @param string $filePath 本地文件路径
     * @return mixed
     */
    public function putFile($fileName, $filePath)
    {
        $uploadToken = $this->auth->uploadToken($this->bucket);
        list($ret, $error) = $this->getClient()->putFile($uploadToken, $fileName, $filePath);
        if(is_null($error)) {
            return true;
        }
        return false;
    }

    public function delete($key)
    {
        if (!isset($this->managers['bucket'])) {
            $this->managers['bucket'] = new BucketManager($this->auth);
        }
        return $this->managers['bucket']->delete($this->bucket, $key);
    }

    /**
     * 列取空间的文件列表
     *
     * @param $bucket     空间名
     * @param $prefix     列举前缀
     * @param $marker     列举标识符
     * @param $limit      单次列举个数限制
     * @param $delimiter  指定目录分隔符
     */
    public function listFiles($prefix = null, $marker = null, $limit = 1000, $delimiter = null)
    {
        if (!isset($this->managers['bucket'])) {
            $this->managers['bucket'] = new BucketManager($this->auth);
        }
        return $this->managers['bucket']->listFiles($this->bucket, $prefix, $marker, $limit, $delimiter);
    }

    /**
     * 获取上传凭证
     * @param string|null $bucket
     * @param string|null $key
     * @param int $expires
     * @param array|null $policy
     * @return mixed
     */
    public function uploadToken($bucket = null, $key = null, $expires = 3600, $policy = null)  {
        if ($bucket === null) {
            $bucket = $this->bucket;
        }
        return $this->auth->uploadToken($bucket, $key, $expires, $policy,  true);
    }
}