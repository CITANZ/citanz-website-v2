<?php

namespace App\Web\Model;

use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use App\Web\Layout\SignupPage;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class PageHero extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'PageHero';
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title'     =>  'Varchar(128)',
        'HeroType'  =>  'Enum("Single,Double")'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'WideHero'      =>  CitaCroppableImage::class,
        'SquareHero'    =>  CitaCroppableImage::class
    ];

    /**
     * Belongs_to relationship
     * @var array
     */
    private static $belongs_to = [
        'Page'  =>  Page::class
    ];

    public function getData()
    {
        $list   =   [];
        if ($this->HeroType == 'Single') {
            if ($this->WideHero()->exists()) {
                $list[] =   $this->WideHero()->getData('ScaleWidth', 1320);
            }
        } else {
            if ($this->WideHero()->exists()) {
                $list[] =   $this->WideHero()->getData('ScaleWidth', 690);
            }

            if ($this->SquareHero()->exists()) {
                $list[] =   $this->SquareHero()->getData('ScaleWidth', 476);
            }
        }

        return !empty($list) ? $list : null;
    }


    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'WideHeroID',
            'SquareHeroID'
        ]);

        $fields->fieldByName('Root.Main.HeroType')->setEmptyString('- select one to continue -');

        if ($this->HeroType == 'Single') {
            $ratio = 16/7.85;
            if ($this->Page()->exists() && ($this->Page() instanceof SignupPage || $this->Page() instanceof Page)) {
                $ratio = 16 / 4;
            }

            $fields->addFieldToTab(
                'Root.Main',
                CroppableImageField::create('WideHero', 'Hero Image', $this)->setCropperRatio($ratio)
            );
        } elseif ($this->HeroType == 'Double') {
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    CroppableImageField::create('WideHero', 'Wide Hero Image', $this)->setCropperRatio(676/424),
                    CroppableImageField::create('SquareHero', 'Square Hero Image', $this)->setCropperRatio(476/424)
                ]
            );
        }

        return $fields;
    }
}
