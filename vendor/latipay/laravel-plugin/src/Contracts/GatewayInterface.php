<?php

namespace Latipay\LaravelPlugin\Contracts;

interface GatewayInterface
{

    public function pay($endpoint, array $payload);
}
