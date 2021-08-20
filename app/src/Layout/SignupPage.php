<?php

namespace App\Web\Layout;

use SilverStripe\Versioned\Versioned;
use Page;

class SignupPage extends Page
{
    private static $table_name = 'SignupPage';

    public function canCreate($member = null, $context = [])
    {
        if (Versioned::get_by_stage(__CLASS__, 'Stage')->exists()) {
            return false;
        }

        return parent::canCreate($member, $context);
    }
}
