<?php

namespace Leochenftw\Restful;

class RestUserException extends \Exception
{
    protected $httpStatusCode = 400;

    public function __construct($message, $errorCode, $httpStatusCode = 400)
    {
        parent::__construct($message, $errorCode);
        $this->httpStatusCode = $httpStatusCode;
    }

    public function getHttpStatusCode()
    {
        return $this->httpStatusCode;
    }

    public function setHttpStatusCode($httpStatusCode)
    {
        $this->httpStatusCode = $httpStatusCode;
        return $this;
    }

}
