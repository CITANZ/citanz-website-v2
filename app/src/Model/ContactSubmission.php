<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use Leochenftw\Debugger;
use App\Web\Email\ContactSubmissionAcknowledgement;
use App\Web\Email\ContactSubmissionNotice;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ContactSubmission extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ContactSubmission';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'Text',
        'Name'      =>  'Varchar(128)',
        'Email'     =>  'Varchar(256)'
    ];

    public function send_email()
    {
        $notice =   ContactSubmissionNotice::create($this);
        $ack    =   ContactSubmissionAcknowledgement::create($this);

        $notice->send();
        $ack->send();
    }
}
