<?php

namespace App\Web\API;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;

use Cita\eCommerce\Model\Customer;
use PMW\Util\PasswordStrength;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;

class Member extends RestfulController
{
    use OAuthTrait;

    private $user = null;
    /**
     * Defines methods that can be called directly.
     *
     * @var array
     */
    private static $allowed_actions = [
        'get' => true,
        'post' => true,
    ];

    public function get($request)
    {
        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError('Unauthorised', 401);
        }

        if ($action = $request->param('action')) {
            return $this->$action($request);
        }

        return $this->user;
    }

    public function post($request)
    {
        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError('Unauthorised', 401);
        }

        if ($action = $request->param('action')) {
            return $this->$action($request);
        }

        return $this->user;
    }

    public function resendActiviationCode(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Method not allowed');
        }

        $session = $this->request->getSession();
        $lastSent = $session->get('lastVerificationSent') ?? time();
        $elapsed = time() - $lastSent;

        if ($elapsed < 60) {
            return $this->httpError(400, 'Request too frequently! (' . (60 - $elapsed) . ' second(s) to go)');
        }

        $this->user->SendVerificationEmail();

        $session->set('lastVerificationSent', time());

        return [
            'message' => '<p>Your activation code has been sent to your email address. Make sure you also check your spambox.</p>',
        ];
    }

    public function doActivate(&$request)
    {
        $code = $request->postVar('activationCode');

        if (empty($code)) {
            return $this->httpError(400, 'Missing activation code!');
        }

        $found = $this->user->VerificationCodes()->filter([
            'Code' => $code,
            'Type' => 'activation',
            'Invalid' => false,
        ])->first();

        if ($found) {
            $found->Invalid = true;
            $found->write();
            $this->user->Verified = true;
            $this->user->write();

            return [
                'message' => 'Thanks! Your account has been activated!',
                'user' => $this->user->jsonSerialize(),
            ];
        }

        return $this->httpError(400, 'Invalid activation code!');
    }
}
