<?php

namespace App\Web\JobReferral\Model;

use SilverStripe\Assets\File;
use SilverStripe\ORM\DataObject;
use Cita\eCommerce\Model\Customer;

/**
 * Description
 * 
 * @package silverstripe
 * @subpackage mysite
 */
class JobApplication extends DataObject
{
    /**
     * Defines the database table name
     *  @var string
     */
    private static string $table_name = 'JobApplication';
    
    private static array $db = [];

    private static array $has_one = [
        'CoverLetter' => File::class,
        'Resume' => File::class,
        'Job' => ReferralOpportunity::class,
        'Applicant' => Customer::class,
    ];
}