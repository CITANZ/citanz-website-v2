<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class ContactSubmissionAcknowledgement extends Email
{
    public function __construct($submission) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $submission->Email;
        $subject    =   'Thank you for your contact!';

        parent::__construct($from, $to, $subject);

        if (Director::isLive()) {
            $this->setBCC(Config::inst()->get(Email::class, 'submission_bcc_email'));
        }

        $this->setHTMLTemplate('Email\\ContactSubmissionAcknowledgement');

        $this->setData([
            'Submission'    =>  $submission,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
