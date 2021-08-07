<?php

namespace App\Web\Model;
use SilverStripe\ORM\DataObject;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;

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
                $list[] =   $this->WideHero()->getCropped()->getData('ScaleWidth', [420, 768, 1320]);
            }
        } else {
            if ($this->WideHero()->exists()) {
                $list[] =   $this->WideHero()->getCropped()->getData('ScaleWidth', [181, 380, 690]);
            }

            if ($this->SquareHero()->exists()) {
                $list[] =   $this->SquareHero()->getCropped()->getData('ScaleWidth', [122, 476]);
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
            $fields->addFieldToTab(
                'Root.Main',
                CroppableImageField::create('WideHero', 'Hero Image', $this)->setCropperRatio(16/7.85)
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
