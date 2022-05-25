<?php

namespace Latipay\LaravelPlugin\Exceptions;

class InvalidSignException extends Exception
{

    /**
     * InvalidGatewayException constructor.
     *
     * @param       $message
     * @param array $raw
     */
    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_SIGN:'.$message, $raw, self::INVALID_SIGN);
    }
}
