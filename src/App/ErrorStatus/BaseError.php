<?php

namespace App\ErrorStatus;

abstract class BaseError
{
    public $statusCode;

    public $developerMessage;

    public $userMessage;

    public $errorCode;

    public function __construct($message = null)
    {
        if ($message !== null) {
            $this->developerMessage = $message;
        }
    }

    public function toArray()
    {
        return [
            'StatusCode' => $this->statusCode,
            'DeveloperMessage' => $this->developerMessage,
            'UserMessage' => null,
            'ErrorCode' => null,
        ];
    }

    /**
     * @return int
     */
    public function getStatusCode()
    {
        return $this->statusCode;
    }

    /**
     * @return string
     */
    public function getErrorCode()
    {
        return $this->errorCode;
    }
}
