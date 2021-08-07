<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class MemberQRCodeEmail extends Email
{
    public function __construct($member) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $member->Email;
        $subject    =   'Your CITA member QR code\'s here';

        parent::__construct($from, $to, $subject);

        $this->setHTMLTemplate('Email\\MemberQRCodeEmail');

        $this->setData([
            'Member'        =>  $member,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
