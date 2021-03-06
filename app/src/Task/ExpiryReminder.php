<?php

namespace App\Web\Task;

use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use Cita\eCommerce\Model\CustomerGroup;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;

class ExpiryReminder extends BuildTask
{
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Expiry Reminder';

    private static $segment = 'expiry-reminder';

    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = '';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $group = CustomerGroup::get()->filter(['Title:nocase' => 'Paid members'])->first();

        if ($group) {
            $members = $group->Customers()->filter([
              'NeverExpire' => false,
              'Expiry:not' => null,
              'Expiry:LessThanOrEqual' => strtotime(date('Y-m-d', time()) . ' +31 days'),
            ]);

            foreach ($members->toArray() as $member) {
                $expiry = strtotime($member->Expiry);
                $deadline = strtotime($member->Expiry . ' +30 days');

                if ($expiry <= time()) {
                    if (!$member->Expiry30Reminded) {
                        echo $member->FirstName . "'s membership expiring in 30 days.";
                        $this->sendExpiryReminder($member, 30);
                    } elseif (!$member->Expiry7Reminded) {
                        echo $member->FirstName . "'s membership expiring in 7 days.";
                        $this->sendExpiryReminder($member, 7);
                    } elseif (!$member->Expiry0Reminded){
                        $this->sendExpiredNotice($member);
                        $member->updateMailchimpPaidTag();
                        echo $member->FirstName . "'s membership has expired";
                    } else {
                        echo $member->FirstName . ' expired and reminded. ignore';
                    }

                    echo PHP_EOL;
                }

                if ($deadline <= time()) {
                    echo $member->FirstName . "'s removed from paid members";
                    echo PHP_EOL;
                    $group->Customers()->remove($member);
                    // once your membership is up, your student status is up too
                    $studentGroup = CustomerGroup::get()->filter(['Title:nocase' => 'Student members'])->first();
                    if ($studentGroup->Customers()->byID($member->ID)) {
                        $studentGroup->Customers()->remove($member);
                    }

                    $this->sendMembershipTerminatedNotice($member);
                    $this->notifyAdminMembershipEnded($member);
                }
            }
        }
    }

    private function notifyAdminMembershipEnded($member)
    {
        $name = trim("{$member->FirstName} {$member->LastName}");
        $citaId = $member->CitaID;

        $recipients = explode(',', SiteConfig::current_site_config()->AccountAffairsRecipient);
        foreach ($recipients as $recipient) {
            $recipient = trim($recipient);

            $email = Email::create(
              'noreply@cita.org.nz',
              $recipient,
              "[CITANZ] $name ($citaId) has been removed from paid member group"
            );
            $link = Environment::getEnv('SS_BASE_URL') . "admin/customers/Cita-eCommerce-Model-Customer/EditForm/field/Cita-eCommerce-Model-Customer/item/{$member->ID}/edit";

            $body = <<<MSG
<p>Hi admin</p>

<p><a href="$link" target="_blank">$name ($citaId)</a> has been removed from paid member, because he/she didn't renew his/her membership after 30 days from the expiry date.</p>

<p>Kind regards<br />
CITANZ</p>
MSG;
            $email->setBody($body);
            $email->send();
        }
    }

    private function sendExpiryReminder($member, $days)
    {
        $email = Email::create('noreply@cita.org.nz', $member->Email, '[CITANZ] Membership has ended');
        $link = Environment::getEnv('SS_BASE_URL') . 'member/membership';

        $body = <<<MSG
  <p>Hi {$member->FirstName}</p>

  <p>Your CITANZ membership is going to expire in {$days} days.</p>

  <p>If <a href="$link" target="_blank">renew now</a>, you can receive a 20% off discount. (Students will get a 50% off discount)</p>

  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();

        $field = "Expiry{$days}Reminded";

        $member->$field = true;
        $member->write();
    }

    private function sendMembershipTerminatedNotice($member)
    {
        $email = Email::create('noreply@cita.org.nz', $member->Email, '[CITANZ] Membership has ended');
        $link = Environment::getEnv('SS_BASE_URL') . 'member';

        $body = <<<MSG
<p>Hi {$member->FirstName}</p>

<p>It's just a notice letting you know that you have been removed from CITANZ's member group, for failing to renew the membership after 30 days from the membership expiration.</p>

<p>You can check your account's status at <a href="$link" target="_blank">our member centre</a>.</p>

<p>If you believe this email is sent by mistake, please contact us via <a href="mailto:membership@cita.org.nz" target="_blank">membership@cita.org.nz</a>.</p>

<p>Thanks for supporting us over the past years, and we sincerely hope our paths will cross again!</p>

<p>Kind regards<br />
CITANZ</p>
MSG;
        $email->setBody($body);
        $email->send();
    }

    private function sendExpiredNotice($member)
    {
        $email = Email::create('noreply@cita.org.nz', $member->Email, '[CITANZ] Membership has expired');
        $discountValidUntil = date('d/m/Y', strtotime($member->Expiry . ' +30 days'));
        $link = Environment::getEnv('SS_BASE_URL') . 'member/membership';

        $body = <<<MSG
<p>Hi {$member->FirstName}</p>

<p>It's a notice that your CITANZ's membership has expired, but the renewal discount (20% off or 50% off for students) will still last until {$discountValidUntil}.</p>

<p><a href="$link" target="_blank">Renew now</a></p>

<p>Kind regards<br />
CITANZ</p>
MSG;
        $email->setBody($body);
        $email->send();

        $member->Expiry0Reminded = true;
        $member->write();
    }
}
