<?php

namespace App\Web\Task;

use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use Leochenftw\Restful\RestfulController;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Leochenftw\Debugger;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\CustomerGroup;

class SyncMemberMailchimp extends BuildTask
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
    protected $title = 'SyncMemberMailchimp';
    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = '';

    private static $segment = 'sync-member-mailchimp';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        $group = CustomerGroup::get()->filter(['Title:nocase' => 'Paid members'])->first();

        if ($group) {
            $members = $group->Customers();
            foreach ($members->toArray() as $member) {
                $result = $member->syncToMailchimp();
                echo $result->detail ?? "$member->Email has been added to Mailchimp";
                echo PHP_EOL;
                $result = $member->updateMailchimpPaidTag();
                echo $result->detail ?? "{$member->Email}'s paid status has been updated on Mailchimp'";
                echo PHP_EOL;
                echo PHP_EOL;
            }
        }
    }
}
