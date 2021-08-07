<?php

namespace Leochenftw\Restful;

/**
 * The system exception can be used for showing a system error like a missing file or a broken connection.
 * @author Christian Blank <c.blank@notthatbad.net>
 */
class RestSystemException extends \Exception
{
    protected $httpStatusCode;

    public function __construct($message, $errorCode, $httpStatusCode = 500)
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
