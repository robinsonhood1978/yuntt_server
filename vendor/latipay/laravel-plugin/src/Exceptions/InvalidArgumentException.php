<?php

/*
 * This file is part of the overtrue/wechat.
 *
 * (c) overtrue <i@overtrue.me>
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

namespace Latipay\LaravelPlugin\Exceptions;

/**
 * Class InvalidArgumentException.
 *
 * @author overtrue <i@overtrue.me>
 */
class InvalidArgumentException extends Exception
{

    public function __construct($message, $raw = [])
    {
        parent::__construct('INVALID_ARGUMENT:'.$message, $raw, self::INVALID_ARGUMENT);
    }
}
