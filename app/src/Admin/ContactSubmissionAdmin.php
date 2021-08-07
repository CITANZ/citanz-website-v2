<?php

namespace App\Web\Admin;
use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\ContactSubmission;
use SilverStripe\Forms\GridField\GridFieldDetailForm;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ContactSubmissionAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        ContactSubmission::class
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'contact-submissions';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Contact Submissions';

    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        if($this->modelClass == ContactSubmission::class) {
            $form
            ->Fields()
            ->fieldByName($this->sanitiseClassName($this->modelClass))
            ->getConfig()
            ->getComponentByType(GridFieldDetailForm::class)
            ->setItemRequestClass(ContactSubmissionGridFieldDetailForm_ItemRequest::class);
        }
        return $form;
    }
}
