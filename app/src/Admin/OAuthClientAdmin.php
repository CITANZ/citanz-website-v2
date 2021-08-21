<?php

namespace App\Web\Admin;

use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\OAuth\Client;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class OAuthClientAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Client::class,
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'oauth-clients';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'OAuth Clients';
}
