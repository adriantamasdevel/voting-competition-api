<?php

namespace App\ErrorStatus;

class BadRequestError extends BaseError
{
    public $statusCode = 400;

    public $developerMessage = "Bad request";

    public $userMessage = null;

    public $errorCode = '400';

}
