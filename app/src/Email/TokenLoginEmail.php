<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use App\Web\Layout\RecoveryController;

class TokenLoginEmail extends Email
{
    public function __construct($member) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $member->Email;
        $subject    =   'Your one-off login token for CITA NZ website';

        parent::__construct($from, $to, $subject);

        if (Director::isLive()) {
            $this->setBCC(Config::inst()->get(Email::class, 'leo.chen@cita.org.nz'));
        }

        $recovery   =   RecoveryController::create();
        $params     =   [
            'id'    =>  $member->ID,
            'token' =>  $member->ValidationKey
        ];

        $token_url  =   $recovery->AbsoluteLink() . '?' . http_build_query($params);

        $this->setHTMLTemplate('Email\\TokenLoginEmail');

        $this->setData([
            'Member'        =>  $member,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'TokenURL'      =>  $token_url,
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
