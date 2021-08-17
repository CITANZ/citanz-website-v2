<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;
use Cita\eCommerce\Model\Customer;

class AuthCode extends DataObject
{
    private static $table_name = 'OAuth_AuthCode';

    private static $db = [
        'GUID' => 'Varchar(120)',
        'Scopes' => 'Text',
        'Revoked' => 'Boolean',
        'ExpiresAt' => 'Datetime',
    ];

    private static $has_one = [
        'Customer' => Customer::class,
        'Client' => Client::class,
    ];

    private static $indexes = [
        'GUID' => [
            'type' => 'unique',
        ],
    ];
}
