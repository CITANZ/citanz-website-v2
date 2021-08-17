<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;

class PersonalAccessToken extends DataObject
{
    private static $table_name = 'OAuth_PersonalAccessToken';

    private static $db = [

    ];

    private static $has_one = [
        'Client' => Client::class,
    ];
}
