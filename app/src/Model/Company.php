<?php

namespace App\Web\Model;

use Cocur\Slugify\Slugify;
use gorriecoe\LinkField\LinkField;
use gorriecoe\Link\Models\Link;
use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use App\Web\Extension\SortOrderExtension;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Company extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Company';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' =>  'Varchar(32)'
    ];

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Logo',
        'MiniLogo'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Logo'      =>  Image::class,
        'MiniLogo'  =>  Image::class,
        'Link'      =>  Link::class
    ];

    public function getData()
    {
        $slugify            =   new Slugify();
        return [
            'title'         =>  $this->Title,
            'classname'     =>  $slugify->slugify($this->Title),
            'logo'          =>  $this->Logo()->exists() ?
                                $this->Logo()->getAbsoluteURL() : null,
            'mini_logo'     =>  $this->MiniLogo()->exists() ?
                                $this->MiniLogo()->getAbsoluteURL() : null,
            'link'          =>  $this->Link()->exists() ?
                                $this->Link()->getData() : null
        ];
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'LinkID'
        ]);

        $fields->addFieldToTab(
            'Root.Main',
            LinkField::create('Link', 'Link', $this)
        );

        $fields->fieldByName('Root.Main.MiniLogo')->setDescription('This is used in featured job section on the homepage');

        return $fields;
    }
}
