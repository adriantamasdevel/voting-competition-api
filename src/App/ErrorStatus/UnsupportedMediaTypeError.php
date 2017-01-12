<?php

namespace App\ErrorStatus;

class UnsupportedMediaTypeError extends BaseError
{
    public $statusCode = 415;

    public $developerMessage = "Unsupported Media Type";

    public $userMessage = null;

    public $errorCode = '415';
}
