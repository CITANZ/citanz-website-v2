<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;

class RefreshToken extends DataObject
{
    private static $table_name = 'OAuth_RefreshToken';

    private static $db = [
        'GUID' => 'Varchar(120)',
        'Revoked' => 'Boolean',
        'ExpiresAt' => 'Datetime',
    ];

    private static $has_one = [
        'AccessToken' => AccessToken::class,
    ];

    private static $indexes = [
        'GUID' => [
            'type' => 'unique',
        ],
    ];
}
