<?php

namespace App\Web\Model;

use gorriecoe\LinkField\LinkField;
use gorriecoe\Link\Models\Link;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use SilverStripe\ORM\DataObject;
use Leochenftw\Extension\SortOrderExtension;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class ImagedBlock extends DataObject
{
    private $width  =   576;
    private $height =   344;
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'ImagedBlock';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'Subtitle'  =>  'Varchar(64)'
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
        'Image' =>  CitaCroppableImage::class,
        'Link'  =>  Link::class,
        'Page'  =>  Page::class
    ];

    public function getData()
    {
        $data   =   [
            'title'     =>  $this->Title,
            'subtitle'  =>  $this->Subtitle,
            'image'     =>  $this->Image()->exists() ?
                            $this->Image()->getCropped()->getData('ScaleWidth', [366, 324, $this->width]) : null,
            'link'      =>  $this->Link()->getData()
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
            'LinkID',
            'ImageID',
            'PageID',
        ]);
        $fields->addFieldsToTab(
            'Root.Main',
            [
                CroppableImageField::create('Image', 'Image', $this)->setCropperRatio($this->width/$this->height),
                LinkField::create(
                    'Link',
                    'Link',
                    $this
                )
            ]
        );
        return $fields;
    }

    public function setCropperRatio($width, $height)
    {
        $this->width    =   $width;
        $this->height   =   $height;

        return $this;
    }
}
