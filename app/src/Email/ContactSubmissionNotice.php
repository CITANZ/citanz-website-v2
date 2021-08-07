<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;

class ContactSubmissionNotice extends Email
{
    public function __construct($submission) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   Director::isLive() ?
                        SiteConfig::current_site_config()->ContactRecipients :
                        Config::inst()->get(Email::class, 'admin_email');
        $subject    =   'Web contact notice';

        parent::__construct($from, $to, $subject);

        $this->setReplyTo($submission->Email);

        if (Director::isLive()) {
            if (!empty(SiteConfig::current_site_config()->ContactBcc)) {
                $this->setBCC(SiteConfig::current_site_config()->ContactBcc);
            } else {
                $this->setBCC(Config::inst()->get(Email::class, 'submission_bcc_email'));
            }
        } else {
            $this->setBCC(Config::inst()->get(Email::class, 'submission_bcc_email'));
        }

        $this->setHTMLTemplate('Email\\ContactSubmissionNotice');

        $this->setData([
            'Submission'    =>  $submission,
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
