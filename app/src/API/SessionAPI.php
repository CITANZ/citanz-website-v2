<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\Member;

class SessionAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'       =>  true
    ];

    public function get($request)
    {
        return  [
            'csrf'      =>  SecurityToken::inst()->getSecurityID(),
            'member'    =>  Member::currentUser() ? Member::currentUser()->getData() : null
        ];
    }
}
