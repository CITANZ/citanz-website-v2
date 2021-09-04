<?php

namespace App\Web\Task;

use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use Cita\eCommerce\Model\Customer;
use SilverStripe\Dev\CSVParser;

class ImportMembers extends BuildTask
{
    /**
     * @var bool $enabled If set to FALSE, keep it from showing in the list
     * and from being executable through URL or CLI.
     */
    protected $enabled = true;

    private static $segment = 'import-members';

    /**
     * @var string $title Shown in the overview on the TaskRunner
     * HTML or CLI interface. Should be short and concise, no HTML allowed.
     */
    protected $title = 'Import members';

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
            $args   =   $request->getVar('args');

            if (count($args) == 0) {
                print 'Missing arguments';
                print PHP_EOL;
                print 'Usage: sake dev/tasks/' . str_replace('\\', '-', get_class($this)) . ' {path_to_file}';
                print PHP_EOL;
                print PHP_EOL;

                return false;
            }

            $csvFile  =   $args[0];

            if (file_exists($csvFile)) {
                $data = new CSVParser($csvFile);

                foreach ($data as $item) {
                    $member = Customer::get()->filter(['Email' => $item['Email']])->first();
                    $member = $member ?? Customer::create();
                    $member->update($item)->write();

                    print $item['Email'] . ' has been imported/updated!' . PHP_EOL;
                }
            }
        } else {
            $this->printHints();
        }

        print PHP_EOL;
    }

    private function printHints()
    {
        print '<span style="color: red; font-weight: bold; font-size: 24px;">This task is for CLI use only</span><br />';
        print '<em style="font-size: 14px;"><strong>Usage</strong>: sake dev/tasks/' . str_replace('\\', '-', get_class($this)) . ' {path_to_file}';
    }
}
