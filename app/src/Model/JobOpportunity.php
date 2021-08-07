<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use Page;
use Leochenftw\Extension\SortOrderExtension;
use Leochenftw\Util;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class JobOpportunity extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'JobOpportunity';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'HTMLText',
        'City'      =>  'Varchar(32)',
        'Country'   =>  'Varchar(32)'
    ];

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
        'Company'   =>  Company::class,
        'Page'      =>  Page::class
    ];

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'content'   =>  Util::preprocess_content($this->Content),
            'city'      =>  $this->City,
            'country'   =>  $this->Country,
            'company'   =>  $this->Company()->exists() ? $this->Company()->getData() : null
        ];
    }

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'Country'   =>  'New Zealand'
    ];

    public function getCMSFields()
    {
      $fields = parent::getCMSFields();
      $fields->removeByName([
          'PageID',
      ]);
      return $fields;
    }
}
