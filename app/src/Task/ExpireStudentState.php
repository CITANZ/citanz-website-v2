<?php

namespace App\Web\Task;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Email\Email;
use App\Web\Model\StudentDiscountApplication;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\CustomerGroup;

class ExpireStudentState extends BuildTask
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
    protected $title = 'Expire students';

    private static $segment = 'expire-students';

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
        $expired = StudentDiscountApplication::get()->filter([
            'ExpiryDate:LessThan' => time(),
        ]);

        $studentGroup = CustomerGroup::get()->filter(['Title:nocase' => 'student members'])->first();

        foreach ($expired as $item) {
            if ($item->Customer()->exists()) {
                echo 'Removing ' . $item->Customer()->Email . ' from student group.' . PHP_EOL;
                $studentGroup->Customers()->remove($item->Customer());
                $this->sendEmail($item->Customer());
            }

            $item->delete();
        }
    }

    private function sendEmail($recipient)
    {
        $email = Email::create('noreply@cita.org.nz', $recipient->Email, '[CITANZ] Student state has expired');

        $body = <<<MSG
  <p>Hi $recipient->FirstName</p>

  <p>This is just an FYI that your current student state has expired. Therefore, your CITANZ membership can no longer benefit from the student discount.</p>
  <p>If you would like to update your student state, please log in into <a href="https://www.cita.org.nz/member/membership" target="_blank">CITANZ Membership</a> page, and upload your latest student ID.</p>
  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();
    }
}
