<?php

namespace Latipay\LaravelPlugin;

use Latipay\LaravelPlugin\Contracts\GatewayApplicationInterface;
use Latipay\LaravelPlugin\Exceptions\InvalidGatewayException;
use Latipay\LaravelPlugin\Kernel\Supports\Config;
use Latipay\LaravelPlugin\Kernel\Supports\Str;

class Pay
{
    protected $config;

    public function __construct(array $config)
    {
        $this->config = new Config($config);
    }

    /**
     * @param $method
     * @param $params
     *
     * @return GatewayApplicationInterface
     * @throws InvalidGatewayException
     */
    public static function __callStatic($method, $params)
    {
        $app = new self(...$params);

        return $app->create($method);
    }

    /**
     * @param $method
     *
     * @return GatewayApplicationInterface
     * @throws InvalidGatewayException
     */
    protected function create($method)
    {
        $gateway = __NAMESPACE__.'\\Gateways\\'.Str::studly($method);

        if (class_exists($gateway)) {
            return self::make($gateway);
        }

        throw new InvalidGatewayException("Gateway [{$method}] Not Exists");
    }

    /**
     * @param $gateway
     *
     * @return GatewayApplicationInterface
     * @throws InvalidGatewayException
     */
    protected function make($gateway)
    {
        $app = new $gateway($this->config);

        if ($app instanceof GatewayApplicationInterface) {
            return $app;
        }

        throw new InvalidGatewayException("Gateway [{$gateway}] Must Be An Instance Of GatewayApplicationInterface");
    }
}
