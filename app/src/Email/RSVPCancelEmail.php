<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use App\Web\Layout\RecoveryController;
use Leochenftw\SSEvent\Model\RSVP;

class RSVPCancelEmail extends Email
{
    public function __construct($first_name, $email, $event_title) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $email;
        $subject    =   '[CITA Event Cancelled] ' . $event_title;

        parent::__construct($from, $to, $subject);

        $this->setHTMLTemplate('Email\\RSVPCancelEmail');

        $this->setData([
            'FirstName'     =>  $first_name,
            'EventTitle'    =>  $event_title,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
