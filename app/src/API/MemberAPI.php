<?php

namespace App\Web\API;
use Leochenftw\Restful\RestfulController;
use SilverStripe\Security\SecurityToken;
use SilverStripe\Security\Member;
use Leochenftw\Debugger;
use Leochenftw\Util;


class MemberAPI extends RestfulController
{
    private $member =   null;

    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'get'       =>  true,
        'post'      =>  true
    ];

    public function post($request)
    {
        $action =   $request->param('Action');
        if (empty($action)) {
            return $this->httpError(400, 'Missing action');
        }

        if (Util::check_csrf($request)) {
            if ($action == 'password_recovery') {
                return $this->do_password_recovery($request);
            }

            $this->member   =   Member::currentUser();

            if (!$this->member) {
                return $this->httpError(403, 'Please log in first!');
            }

            if ($this->hasMethod('do_' . $action)) {
                return $this->{'do_' . $action}($request);
            }

            return $this->httpError(403, 'Call to not allowed action');
        }

        return $this->httpError(400, 'Invalid CSRF token');
    }

    private function do_basic_update(&$request)
    {
        if (empty($request->postVar('firstname')) || Util::null_it($request->postVar('firstname'))) {
            return $this->httpError(400, 'You must enter your first name');
        }

        if (empty($request->postVar('surname')) || Util::null_it($request->postVar('surname'))) {
            return $this->httpError(400, 'You must enter your last name');
        }

        if (empty($request->postVar('email')) || Util::null_it($request->postVar('email'))) {
            return $this->httpError(400, 'You must enter your email address');
        }

        $this->member->FirstName    =   $request->postVar('firstname');
        $this->member->Surname      =   $request->postVar('surname');
        $this->member->Email        =   $request->postVar('email');
        $this->member->write();

        return ['message' => 'Your basic information has been updated.'];
    }

    private function do_password(&$request)
    {
        if ($request->postVar('pass')) {
            $this->member->Password =   $request->postVar('pass');
            $this->member->write();
            return ['message' => 'Your password has been updated.'];
        }

        return $this->httpError(400, 'Please enter the new password!');
    }

    private function do_password_recovery(&$request)
    {
        if ($email = Util::null_it($request->postVar('email'))) {
            if ($member = Member::get()->filter(['Email' => $email])->first()) {
                $member->password_recovery();
            }
            return ['message' => 'If you already have an account with us, you will receive a password reset email shortly.'];
        }

        return $this->httpError(400, 'Email is missing!');
    }

    public function get($request)
    {
        if ($member =   Member::currentUser()) {
            return $member->getData();
        }

        return $this->httpError(403, 'Please log in first!');
    }
}
