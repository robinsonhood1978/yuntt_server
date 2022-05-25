<?php

namespace Latipay\LaravelPlugin\Exceptions;

class InvalidGatewayException extends Exception
{

    /**
     * InvalidGatewayException constructor.
     *
     * @param       $message
     * @param array $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_GATEWAY:'.$message, $raw, self::INVALID_GATEWAY);
    }
}
