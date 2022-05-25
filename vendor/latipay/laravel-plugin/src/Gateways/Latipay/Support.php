<?php

namespace Latipay\LaravelPlugin\Gateways\Latipay;

use Latipay\LaravelPlugin\Gateways\Latipay;
use Latipay\LaravelPlugin\Kernel\Supports\Config;
use Latipay\LaravelPlugin\Kernel\Traits\HasHttpRequests;

class Support
{
    use HasHttpRequests;

    protected $baseUri;

    protected $paymentMethodUri;

    protected $config;

    /**
     * Instance.
     *
     * @var Support
     */
    private static $instance;

    private function __construct(Config $config)
    {
        $this->baseUri = Latipay::URL[$config->get('mode', Latipay::MODE_V2)];
        $this->paymentMethodUri = Latipay::PAYMENT_METHOD_URL[$config->get('mode', Latipay::MODE_V2)];

        $this->config = $config;

        $this->setHttpOptions();
    }

    public function __get($key)
    {
        return $this->getConfig($key);
    }

    /**
     * create.
     *
     * @param Config $config
     *
     * @return Support
     */
    public static function create(Config $config)
    {
        if (php_sapi_name() === 'cli' || !(self::$instance instanceof self)) {
            self::$instance = new self($config);
        }

        return self::$instance;
    }

    /**
     * clear.
     *
     * @return void
     */
    public function clear()
    {
        self::$instance = null;
    }

    public static function requestApi(array $data, $endpoint, $options = [])
    {
        $data = array_filter($data, function ($value) {
            return ($value == '' || is_null($value)) ? false : true;
        });

        return self::$instance->post($endpoint, json_encode($data), $options);
    }

    public static function requestGetApi($endpoint, $query = [])
    {
        return self::$instance->get($endpoint, $query);
    }

    public function getConfig($key = null, $default = null)
    {
        if (is_null($key)) {
            return $this->config->all();
        }

        if ($this->config->has($key)) {
            return $this->config[$key];
        }

        return $default;
    }

    public function getBaseUri()
    {
        return $this->baseUri;
    }

    public function getPaymentMethodUri()
    {
        return $this->paymentMethodUri;
    }

    protected function setHttpOptions()
    {
        if ($this->config->has('http') && is_array($this->config->get('http'))) {
            $this->config->forget('http.base_uri');
            $this->httpOptions = $this->config->get('http');
        }

        return $this;
    }

    public static function clientIP()
    {
        $cip = null;
        if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
            $cip = $_SERVER["HTTP_CLIENT_IP"];
        } elseif (!empty($_SERVER["HTTP_X_FORWARDED_FOR"])) {
            $cip = $_SERVER["HTTP_X_FORWARDED_FOR"];
        } elseif (!empty($_SERVER["REMOTE_ADDR"])) {
            $cip = $_SERVER["REMOTE_ADDR"];
        }
        return $cip;
    }
}
