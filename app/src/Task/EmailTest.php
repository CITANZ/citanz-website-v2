<?php

namespace App\Web\Task;
use SilverStripe\Dev\Debug;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Control\Email\Email;

class EmailTest extends BuildTask
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
    protected $title = 'Email Test';

    private static $segment = 'test-email';

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
        $args = $request->getVars('args');

        if (!empty($args)) {
            $email = $args['args'][0];

            $this->sendTestEmail($email);

            return;
        }

        Debug::dump('Email?');
    }

    private function sendTestEmail($recipient)
    {
        $email = Email::create('noreply@cita.org.nz', $recipient, '[CITANZ] test email');

        $body = <<<MSG
  <p>Yo</p>

  <p>This is a test email from CITANZ:</p>
  <p>just.. ignore it</p>
  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();
    }
}
