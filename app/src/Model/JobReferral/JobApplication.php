<?php

namespace App\Web\JobReferral\Model;

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
    
    private static array $db = [
        'FirstName' => 'Varchar(16)',
        'LastName' => 'Varchar(16)',
        'Email' => 'Varchar(255)',
        'LinkedIn' => 'Varchar(1024)',
        'WechatID' => 'Varchar(128)',
        'Phone' => 'Varchar(32)',
        'Github' => 'Varchar(32)',
    ];

    private static array $has_one = [
        'Job' => ReferralOpportunity::class,
        'Applicant' => Customer::class,
    ];
}