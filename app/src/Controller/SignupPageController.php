<?php

namespace App\Web\Layout;

use SilverStripe\Dev\Debug;
use PageController;
use SilverStripe\Security\SecurityToken;
use Leochenftw\Util;
use Cita\eCommerce\Model\Customer;
use ZxcvbnPhp\Zxcvbn;

class SignupPageController extends PageController
{
    private $firstName = null;
    private $lastName = null;
    private $password = null;
    private $email = null;
    private $agreed = null;

    private static $allowed_actions = [
        'doSignup' => true
    ];

    private static $url_handlers = [
        'do-signup' => 'doSignup'
    ];

    public function doSignup()
    {
        if (!$this->request->isAjax()) {
            return $this->httpError(404, 'Request type not allowed!');
        }

        if ($this->request->isGet()) {
            return [
                'csrf' => SecurityToken::inst()->getSecurityID(),
            ];
        } elseif ($this->request->isPost()) {
            if (!Util::check_csrf($this->request)) {
                return $this->httpError(400, 'CSRF token is missing');
            }

            $errors = $this->validate();

            if (!empty($errors)) {
                $listedErrors = implode('</li><li>', $errors);
                return $this->httpError(400, "<ul><li>{$listedErrors}</li></ul>");
            }

            if ($this->checkExisting()) {
                return $this->httpError(400, 'The member already exists!');
            }

            return $this->createMember();
        }

        return $this->httpError(400, 'Method not allowed!');
    }

    private function createMember()
    {
        $customer = Customer::create()->update([
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Email' => $this->email,
            'Password' => Customer::hashPassword($this->password),
        ]);

        $customer->write();
        $customer->SendVerificationEmail();
        $customer->syncToMailchimp();

        return [
            'message' => 'Your account has been created. Please check your email for the verification link.',
            'redirect' => '/member/me',
        ];
    }

    private function checkExisting()
    {
        return Customer::get()->filter(['Email' => $this->email])->first();
    }

    private function validate()
    {
        $errors = [];

        $this->firstName = $this->request->postVar('firstName');
        $this->lastName = $this->request->postVar('lastName');
        $this->password = $this->request->postVar('password');
        $this->email = $this->request->postVar('email');
        $this->agreed = !!$this->request->postVar('agreed');

        if (empty($this->firstName)) {
            $errors[] = 'First name is missing';
        }

        if (empty($this->lastName)) {
            $errors[] = 'Last name is missing';
        }

        if (empty($this->password)) {
            $errors[] = 'Password is missing';
        } else {
            if ((new Zxcvbn())->passwordStrength($this->password)['score'] < 2) {
              $errors[] = 'Password is not strong enough!';
            }
        }

        if (empty($this->email)) {
            $errors[] = 'Email is missing';
        }

        if (empty($this->agreed)) {
            $errors[] = 'You must agree and accept our terms and conditions :)';
        }

        return $errors;
    }
}
