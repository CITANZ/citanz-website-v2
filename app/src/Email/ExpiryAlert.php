<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class ExpiryAlert extends Email
{
    public function __construct($member, $stage = 0) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $member->Email;
        $subject    =   'test email';

        parent::__construct($from, $to, $subject);

        if (Director::isLive()) {
            $this->setBCC(Config::inst()->get(Email::class, 'leo.chen@cita.org.nz'));
        }

        $this->setHTMLTemplate('Email\\ExpiryAlert');

        $this->setData([
            'Member'        =>  $member,
            'Stage'         =>  $stage,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
