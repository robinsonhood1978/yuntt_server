<?php

namespace Latipay\LaravelPlugin\Contracts;

interface GatewayApplicationInterface
{

    public function pay($gateway, $params);

    public function find($order, $type);

    public function refund($order);

    public function cancel($order);

    public function close($order);

    public function verify($content, $refund);

    public function success();
}
