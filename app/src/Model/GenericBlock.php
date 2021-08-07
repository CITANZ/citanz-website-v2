<?php

namespace App\Web\Model;
use SilverStripe\ORM\DataObject;
use Leochenftw\Extension\SortOrderExtension;
use Page;
use Leochenftw\Util;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class GenericBlock extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'GenericBlock';

    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'HTMLText'
    ];

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'content'   =>  Util::preprocess_content($this->Content)
        ];
    }

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SortOrderExtension::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Page'  =>  Page::class,
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'PageID'
        ]);
        return $fields;
    }
}
