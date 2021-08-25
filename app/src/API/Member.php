<?php

namespace App\Web\API;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use ZxcvbnPhp\Zxcvbn;
use Cita\eCommerce\Model\Customer;
use PMW\Util\PasswordStrength;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;
use Cita\eCommerce\Model\MemberVerificationCode;

class Member extends RestfulController
{
    use OAuthTrait;

    private const ANONYMOUS_METHODS = [
      'passwordRecovery',
      'setPassword',
    ];

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
        $action = $request->param('action');

        if (in_array($action, static::ANONYMOUS_METHODS)) {
            return $this->$action($request);
        }

        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        if ($action && $this->hasMethod($action)) {
            return $this->$action($request);
        }

        return $this->httpError(401, 'Unauthorised');
    }

    public function setPassword(&$request)
    {
        $token = $request->getSession()->get('passwordReoveryToken');

        if ($token) {
            if ($code = MemberVerificationCode::get()->filter(['Code' => $token, 'Invalid' => false])->first()) {
                if ($password = $request->requestVar('password')) {
                    if ((new Zxcvbn())->passwordStrength($password)['score'] >= 2) {
                        $code->Customer()->update([
                            'Password' => Customer::hashPassword($password),
                        ])->write();

                        $request->getSession()->clear('passwordReoveryToken');

                        return [
                            'message' => 'Your password has been updated! Please sign in again.',
                            'redirect' => '/member/me',
                        ];
                    }

                    return $this->httpError(400, 'Password is not strong enough!');
                }
            }
        }

        return $this->httpError(403, 'Invalid token. Please re-apply password recovery!');
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

    public function passwordRecovery(&$request)
    {
        $email = $request->postVar('email');

        if (!$email) {
            return $this->httpError(400, 'Missing email');
        }

        $member = Customer::get()->filter(['Email' => $email])->first();

        if (!$member) {
            return $this->httpError(404, 'No such account!');
        }

        $member->RequestPasswordReset();

        return [
            'message' => '<p>The password recovery link has been sent to your email address. Make sure you also check your spambox.</p>',
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
