<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TransactionNotReversibleException extends UnprocessableEntityHttpException
{
    public function __construct()
    {
        parent::__construct('The transaction cannot be reversed.');
    }
}
