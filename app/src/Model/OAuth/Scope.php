<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;

class Scope extends DataObject
{
    private static $table_name = 'OAuth_Scope';

    private static $db = [
        'Name' => 'Varchar(255)',
        'GUID' => 'Varchar(120)',
    ];

    private static $indexes = [
        'GUID' => [
            'type' => 'unique',
        ],
    ];
}
