<?php

namespace App\ErrorStatus;

class NotFoundError extends BaseError
{
    public $statusCode = 404;

    public $developerMessage = "Not Found";

    public $userMessage = null;

    public $errorCode = '404';

}
