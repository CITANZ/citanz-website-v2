<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\OptionsetField;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use SilverStripe\Versioned\Versioned;
use Leochenftw\Extension\SortOrderExtension;
use SilverShop\HasOneField\HasOneButtonField;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class WorkingGroupMember extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'WorkingGroupMember';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Excerpt'   =>  'Text',
        'Content'   =>  'HTMLText',
        'Linkedin'  =>  'Varchar(1024)'
    ];

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'linkedin'  =>  $this->Linkedin,
            'portrait'  =>  $this->Portrait()->exists() ?
                            $this->Portrait()->getCropped()->getData('FitMax', 366, 308) :
                            null
        ];
    }

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        Versioned::class,
        SortOrderExtension::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Portrait'  =>  CitaCroppableImage::class,
    ];

    private static $cascade_deletes = ['Portrait'];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'PortraitID',
        ]);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                CroppableImageField::create('Portrait', 'Portrait', $this)->setCropperRatio(282/237),
            ],
            'Title'
        );

        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextField::create('Linkedin'),
                $fields->fieldByName('Root.Main.Excerpt')
            ],
            'Content'
        );

        $fields->fieldByName('Root.Main.Title')->setTitle('Full Name');
        $fields->fieldByName('Root.Main.Linkedin')->setDescription('Make sure you include "http://" or "https://"');
        return $fields;
    }
}
