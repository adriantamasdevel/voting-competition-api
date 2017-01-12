<?php

namespace App\ErrorStatus;

class UnauthorizedError extends BaseError
{
    public $statusCode = 401;

    public $developerMessage = "Unauthorized";

    public $userMessage = null;

    public $errorCode = '401';

}
