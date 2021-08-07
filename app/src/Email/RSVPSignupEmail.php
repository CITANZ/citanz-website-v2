<?php

namespace App\Web\Email;
use SilverStripe\Control\Email\Email;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use App\Web\Layout\RecoveryController;
use Leochenftw\SSEvent\Model\RSVP;

class RSVPSignupEmail extends Email
{
    public function __construct($rsvp) {
        $from       =   Config::inst()->get(Email::class, 'noreply_email');
        $to         =   $rsvp->Email;
        $subject    =   '[CITA Event] Your RSVP for: ' . $rsvp->Event()->Title;

        parent::__construct($from, $to, $subject);

        $this->setHTMLTemplate('Email\\RSVPSignupEmail');
        if ($ical = $rsvp->get_ical_string()) {
            $this->addAttachmentFromData($ical, 'citanz-event.ics', 'text/calendar');
        }
        $this->setData([
            'RSVP'          =>  $rsvp,
            'iCalDebug'     =>  !empty($ical) ? $ical : null,
            'Siteconfig'    =>  SiteConfig::current_site_config(),
            'baseURL'       =>  Director::absoluteURL(Director::baseURL())
        ]);
    }
}
