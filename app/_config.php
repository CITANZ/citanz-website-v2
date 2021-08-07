<?php

use SilverStripe\Admin\CMSMenu;
use SilverStripe\CampaignAdmin\CampaignAdmin;
use SilverStripe\Control\Director;
use SilverStripe\Core\Environment;
use SilverStripe\Reports\ReportAdmin;
use SilverStripe\Security\Member;
use SilverStripe\Security\PasswordValidator;
use SilverStripe\Security\Security;
use Symbiote\QueuedJobs\Controllers\QueuedJobsAdmin;

if (Environment::getEnv('SS_FORCE_SSL')) {
    Director::forceSSL();
}

if (Environment::getEnv('SS_FORCE_WWW')) {
    Director::forceWWW();
}

if ($member = Security::getCurrentUser()) {
    if (!$member->isDefaultadmin()) {
        CMSMenu::remove_menu_class(ReportAdmin::class);
        CMSMenu::remove_menu_class(CampaignAdmin::class);
        CMSMenu::remove_menu_class(QueuedJobsAdmin::class);
    }
}

// remove PasswordValidator for SilverStripe 5.0
$validator = PasswordValidator::create();
$validator->setMinLength(8);
$validator->setHistoricCount(0);
Member::set_password_validator($validator);
