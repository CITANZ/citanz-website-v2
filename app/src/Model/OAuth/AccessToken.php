<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;
use Cita\eCommerce\Model\Customer;

class AccessToken extends DataObject
{
    private static $table_name = 'OAuth_AccessToken';

    private static $db = [
        'GUID' => 'Varchar(120)',
        'Name' => 'Varchar(255)',
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
