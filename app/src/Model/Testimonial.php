<?php

namespace App\Web\Model;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use SilverStripe\ORM\DataObject;
use Leochenftw\Extension\SortOrderExtension;
use App\Web\Layout\HomePage;
use Leochenftw\Util;
use Leochenftw\Debugger;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Testimonial extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Testimonial';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'JobTitle'  =>  'Varchar(64)',
        'Content'   =>  'HTMLText'
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
        'Portrait'  =>  CitaCroppableImage::class,
        'Page'      =>  HomePage::class
    ];

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'job_title' =>  $this->JobTitle,
            'content'   =>  Util::preprocess_content($this->Content),
            'portrait'  =>  $this->get_portrait_data(64, 64)
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
            'PortraitID',
            'PageID',
        ]);
        $fields->addFieldToTab(
            'Root.Main',
            CroppableImageField::create('Portrait', 'Portrait', $this)->setCropperRatio(1),
            'Content'
        );

        return $fields;
    }

    private function get_portrait_data($width, $height)
    {
        if (!$this->Portrait()->exists()) return null;
        $portrait   =   $this->Portrait();
        $cropped    =   $portrait->getCropped();
        return [
            'id'        =>  $portrait->ID,
            'title'     =>  $this->Title,
            'ratio'     =>  ($height / $width) * 10000 / 100,
            'url'       =>  $cropped->FillMax($width * 2, $height * 2)->getAbsoluteURL(),
            'width'     =>  $width,
            'height'    =>  $height
        ];
    }
}
