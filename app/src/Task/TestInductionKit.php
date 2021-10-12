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

class TestInductionKit extends BuildTask
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
    protected $title = 'TestInductionKit';
    /**
     * @var string $description Describe the implications the task has,
     * and the changes it makes. Accepts HTML formatting.
     */
    protected $description = '';

    private static $segment = 'test-induction';

    /**
     * This method called via the TaskRunner
     *
     * @param SS_HTTPRequest $request
     */
    public function run($request)
    {
        if ($request->getHeader('User-Agent') != 'CLI') {
            $this->printHints();
            return;
        }

        if (!empty($request->getVar('args'))) {
            $args = $request->getVar('args');

            if (count($args) == 0) {
                print 'Missing arguments';
                print PHP_EOL;
                print 'Usage: sake dev/tasks/test-induction {MemberID}';
                print PHP_EOL;
                print PHP_EOL;

                return false;
            }

            $ID = (int) $args[0];

            $customer = Customer::get()->byID($ID);

            if (!$customer) {
                echo 'CANNOT FIND MEMBER';
                echo PHP_EOL;
            }

            $customer->sendMemberInductionKit();

            echo 'Induction Kit sent';
            echo PHP_EOL;

            return;
        }

        print 'Missing arguments';
        print PHP_EOL;
        print 'Usage: sake dev/tasks/test-induction {MemberID}';
        print PHP_EOL;
        print PHP_EOL;
    }

    private function printHints()
    {
        print '<span style="color: red; font-weight: bold; font-size: 24px;">This task is for CLI use only</span><br />';
        print '<em style="font-size: 14px;"><strong>Usage</strong>: sake dev/tasks/test-induction {MemberID}';
    }
}
