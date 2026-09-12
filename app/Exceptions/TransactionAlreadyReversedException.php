<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\UnprocessableEntityHttpException;

class TransactionAlreadyReversedException extends UnprocessableEntityHttpException
{
    public function __construct()
    {
        parent::__construct('The transaction has already been reversed.');
    }
}
