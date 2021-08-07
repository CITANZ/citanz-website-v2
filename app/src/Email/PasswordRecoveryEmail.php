<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class PasswordRecoveryEmail extends Email
{
    public function __construct($member) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $member->Email;
        $subject    =   'Playmarket web password recovery link';

        parent::__construct($from, $to, $subject);

        $this->setHTMLTemplate('Email\\Recovery');

        $this->setData([
            'Member'    =>  $member,
            'baseURL'   =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
