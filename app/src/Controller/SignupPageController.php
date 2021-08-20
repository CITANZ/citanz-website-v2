<?php

namespace App\Web\Layout;

use SilverStripe\Dev\Debug;
use PageController;
use SilverStripe\Security\SecurityToken;
use Leochenftw\Util;

class SignupPageController extends PageController
{
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
        }

        return $this->httpError(400, 'Method not allowed!');
    }

    private function validate()
    {
        $errors = [];

        $firstName = $this->request->postVar('firstName');
        $lastName = $this->request->postVar('lastName');
        $password = $this->request->postVar('password');
        $email = $this->request->postVar('email');
        $agreed = !!$this->request->postVar('agreed');

        if (empty($firstName)) {
            $errors[] = 'First name is missing';
        }

        if (empty($lastName)) {
            $errors[] = 'Last name is missing';
        }

        if (empty($password)) {
            $errors[] = 'Password is missing';
        }

        if (empty($email)) {
            $errors[] = 'Email is missing';
        }

        if (empty($agreed)) {
            $errors[] = 'You must agree and accept our terms and conditions :)';
        }

        return $errors;
    }
}
