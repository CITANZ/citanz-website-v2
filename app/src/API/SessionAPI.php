<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\Security\SecurityToken;

class SessionAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get' => true
    ];

    public function get($request)
    {
        $action = $request->param('action');

        if ($this->hasMethod($action)) {
            return $this->$action($request);
        }

        return $this->httpError(404);
    }

    public function getCSRF()
    {
        return SecurityToken::inst()->getSecurityID();
    }
}
