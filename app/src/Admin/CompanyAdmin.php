<?php

namespace App\Web\Admin;
namespace App\Web\Admin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\Company;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class CompanyAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Company::class,
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'companies';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Companies';
}
