<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Core\Environment;
use App\Web\Traits\OAuthTrait;

class SessionAPI extends RestfulController
{
    use OAuthTrait;
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

    public function getAAK(&$request)
    {
        $user = $this->authenticate();

        if (!$user || $user instanceof HTTPResponse || !$user->CanViewListing) {
            return $this->httpError(404, 'Page not found');
        }

        return [
            'key' => Environment::getEnv('AWS_ACCESS_KEY_ID_FE'),
            'secret' => Environment::getEnv('AWS_SECRET_ACCESS_KEY_FE'),
            'region' => Environment::getEnv('AWS_REGION'),
        ];
    }
}
