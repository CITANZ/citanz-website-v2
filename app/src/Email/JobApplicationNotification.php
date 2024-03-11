<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class JobApplicationNotification extends Email
{
    public function __construct($application) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $application->Job()->PostedBy()->Email;
        $subject    =   '[CITANZ] Referral job application: ' . $application->Job()->Title;

        parent::__construct($from, $to, $subject);

        $this->setReplyTo($application->Email);

        $this->setHTMLTemplate('Email\\JobApplicationNotification');

        $this->setData([
            'Application'   =>  $application,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
