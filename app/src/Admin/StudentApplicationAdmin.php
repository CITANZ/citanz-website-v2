<?php

namespace App\Web\Admin;

use SilverStripe\Admin\ModelAdmin;
use App\Web\Model\StudentDiscountApplication;
use SilverStripe\Forms\GridField\GridFieldDetailForm;

class StudentApplicationAdmin extends ModelAdmin
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        StudentDiscountApplication::class,
    ];

    /**
     * URL Path for CMS
     * @var string
     */
    private static $url_segment = 'student-applications';

    /**
     * Menu title for Left and Main CMS
     * @var string
     */
    private static $menu_title = 'Student Applications';

    public function getEditForm($id = null, $fields = null) {
        $form = parent::getEditForm($id, $fields);
        if($this->modelClass == StudentDiscountApplication::class) {
            $form
            ->Fields()
            ->fieldByName($this->sanitiseClassName($this->modelClass))
            ->getConfig()
            ->getComponentByType(GridFieldDetailForm::class)
            ->setItemRequestClass(StudentApplicationGridFieldDetailForm_ItemRequest::class);
        }
        return $form;
    }
}
