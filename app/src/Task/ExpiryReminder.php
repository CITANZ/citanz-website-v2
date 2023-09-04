<?php

namespace App\Web\Task;

use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use Cita\eCommerce\Model\CustomerGroup;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use Cita\eCommerce\Model\Customer;

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
        $isDry = (int) $request->getVar('dryrun') === 1;
        $targetedMember = (int) $request->getVar('member');
        $messages = [];

        if ($isDry && !empty($targetedMember)) {
            $targetedMember = Customer::get()->byID($targetedMember);
            $this->sendExpiryReminder($targetedMember, 30, true);
            $this->sendExpiryReminder($targetedMember, 7, true);
            $this->sendExpiredNotice($targetedMember);
            return;
        }

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
                $earlyReminder30 = strtotime($member->Expiry . ' -30 days');
                $earlyReminder7 = strtotime($member->Expiry . ' -7 days');

                if ($earlyReminder30 <= time() && !$member->Expiry30Reminded) {
                    $messages[] = $member->FirstName . "'s membership expiring in 30 days.";
                    echo $member->FirstName . "'s membership expiring in 30 days." . PHP_EOL;
                    if (!$isDry) {
                        $this->sendExpiryReminder($member, 30);
                    }
                } elseif ($earlyReminder7 <= time() && !$member->Expiry7Reminded) {
                    $messages[] = $member->FirstName . "'s membership expiring in 7 days.";
                    echo $member->FirstName . "'s membership expiring in 7 days." . PHP_EOL;
                    if (!$isDry) {
                        $this->sendExpiryReminder($member, 7);
                    }
                } elseif ($expiry <= time() && !$member->Expiry0Reminded){
                    if (!$isDry) {
                        $this->sendExpiredNotice($member);
                        $member->updateMailchimpPaidTag();
                    }
                    $messages[] = $member->FirstName . "'s membership has expired";
                    echo $member->FirstName . "'s membership has expired" . PHP_EOL;
                } elseif (
                    $member->Expiry30Reminded
                    && $member->Expiry7Reminded
                    && $member->Expiry0Reminded
                ) {
                    $messages[] = $member->FirstName . " expired (" . date('Y-m-d', $expiry) . ") and reminded. ignore";
                    echo $member->FirstName . " expired (" . date('Y-m-d', $expiry) . ") and reminded. ignore" . PHP_EOL;
                }

                if ($deadline <= time()) {
                    $messages[] = $member->FirstName . "'s removed from paid members";
                    echo $member->FirstName . "'s removed from paid members" . PHP_EOL;
                    if (!$isDry) {
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

        $this->reportCrontabRun($messages);
    }

    private function reportCrontabRun($content = [])
    {
        $email = Email::create('noreply@cita.org.nz', 'leochenftw@gmail.com', '[CITANZ] Crontab ran');
        if (empty($content)) {
            $content = '- EMPTY RUN';
        } else {
            $content = trim(implode('<br />', $content));
        }

        $body = <<<MSG
  <p>Hi</p>

  <p>The crontab has run. See content below:</p>
  <p>$content</p>
  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();
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

    private function sendExpiryReminder($member, $days, $isDry = false)
    {
        $email = Email::create('noreply@cita.org.nz', $member->Email, '[CITANZ] Membership is expiring in ' . $days . ' days');
        $link = Environment::getEnv('SS_BASE_URL') . 'member/membership';

        $body = <<<MSG
  <p>Hi {$member->FirstName}</p>

  <p>Your CITANZ membership is going to expire in {$days} days.</p>

  <p><a href="$link" target="_blank">Renew now</a>. (Students will get a 50% off discount)</p>

  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();

        if ($isDry) {
            return;
        }

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

    private function sendExpiredNotice($member, $isDry = false)
    {
        $email = Email::create('noreply@cita.org.nz', $member->Email, '[CITANZ] Membership has expired');
        $discountValidUntil = date('d/m/Y', strtotime($member->Expiry . ' +30 days'));
        $link = Environment::getEnv('SS_BASE_URL') . 'member/membership';

        $body = <<<MSG
<p>Hi {$member->FirstName}</p>

<p>It's a notice that your CITANZ's membership has expired, but the renewal discount (50% off for students) will still last until {$discountValidUntil}.</p>

<p><a href="$link" target="_blank">Renew now</a></p>

<p>Kind regards<br />
CITANZ</p>
MSG;
        $email->setBody($body);
        $email->send();

        if ($isDry) {
            return;
        }

        $member->Expiry0Reminded = true;
        $member->write();
    }
}
