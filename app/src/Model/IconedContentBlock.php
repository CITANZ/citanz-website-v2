<?php

namespace App\Web\Model;
use SilverStripe\Assets\File;
use SilverStripe\Forms\TextField;
use SilverStripe\ORM\DataObject;
use Page;
use App\Web\Extension\SortOrderExtension;
use App\Web\Layout\HomePage;
/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class IconedContentBlock extends GenericBlock
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'IconedContentBlock';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'BadgeText' =>  'Varchar(32)'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Icon'              =>  File::class,
        'MissionPointOf'    =>  HomePage::class,
        'BenefitPointOf'    =>  HomePage::class,
        'OpportunityPointOf'=>  HomePage::class
    ];

    public function getData()
    {
        $data           =   parent::getData();
        $data['badge']  =   $this->BadgeText;
        $data['icon']   =   $this->Icon()->exists() ?
                            $this->Icon()->getAbsoluteURL() : null;

        return $data;
    }

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'Icon'
    ];

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'MissionPointOfID',
            'BenefitPointOfID',
            'OpportunityPointOfID'
        ]);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                $fields->fieldByName('Root.Main.Icon'),
                TextField::create(
                    'BadgeText',
                    'Badge Text'
                )->setDescription('e.g Coming soon')
            ],
            'Content'
        );

        return $fields;
    }
}
