<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\Member;
use SilverStripe\Security\IdentityStore;
use SilverStripe\Core\Injector\Injector;
use Leochenftw\Debugger;
use Leochenftw\Util;

class SignupAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post'  =>  true
    ];

    public function post($request)
    {
        if (!Util::check_csrf($request)) {
            return $this->httpError(400, 'Invalid CSRF token');
        }
        
        return $this->httpError(400, ':/');
    }
}
