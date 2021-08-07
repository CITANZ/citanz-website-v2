<?php

namespace App\Web\Admin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\Team;
use App\Web\Member\BasicMember;
use App\Web\Model\WorkingGroupMember;
use Symbiote\GridFieldExtensions\GridFieldOrderableRows;
use Leochenftw\Debugger;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class TeamAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Team::class,
        WorkingGroupMember::class,
        BasicMember::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'members-teams';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Members & Teams';
}
