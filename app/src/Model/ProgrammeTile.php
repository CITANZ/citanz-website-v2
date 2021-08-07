<?php

namespace App\Web\Model;
use SilverStripe\Versioned\Versioned;
use SilverStripe\ORM\DataObject;
use Leochenftw\Extension\SortOrderExtension;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use Page;
use Leochenftw\Util;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ProgrammeTile extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ProgrammeTile';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Content'   =>  'HTMLText'
    ];

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SortOrderExtension::class,
        Versioned::class
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = array(
        'Image' =>  CitaCroppableImage::class,
        'Page'  =>  Page::class
    );

    public function getData()
    {
        $data   =   [
            'title'     =>  $this->Title,
            'content'   =>  Util::preprocess_content($this->Content),
            'image'     =>  $this->Image()->exists() ?
                            $this->Image()->getCropped()->getData('FillMax', 376, 472) : null
        ];

        return $data;
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'PageID',
            'ImageID',
        ]);
        $fields->addFieldToTab(
            'Root.Main',
            CroppableImageField::create('Image', 'Image', $this)->setCropperRatio(376/472)
        );
        return $fields;
    }
}
