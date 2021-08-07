<?php

namespace App\Web\Model;
use SilverStripe\ORM\DataObject;
use Leochenftw\Grid;
use Leochenftw\Extension\SortOrderExtension;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class Team extends DataObject
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'Team';

    /**
     * Defines extension names and parameters to be applied
     * to this object upon construction.
     * @var array
     */
    private static $extensions = [
        SortOrderExtension::class
    ];

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Title' =>  'Varchar(128)'
    ];
    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Page'  =>  Page::class
    ];
    /**
     * Has_many relationship
     * @var array
     */
    private static $many_many = [
        'Members'   =>  WorkingGroupMember::class
    ];

    private static $many_many_extraFields = [
      'Members' => [
        'SortOrder' => 'Int'
      ]
    ];

    public function getData()
    {
        return [
            'title'     =>  $this->Title,
            'members'   =>  $this->Members()->Data
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
            'PageID'
        ]);
        if ($this->exists()) {
            $fields->removeByName([
                'Members'
            ]);
            $fields->addFieldToTab(
                'Root.Main',
                Grid::make('Members', 'Members', $this->Members(), true, 'GridFieldConfig_RelationEditor', false, 'SortOrder')
            );
        }
        return $fields;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $members    =   $this->Members();
        foreach($members as $member) {
            if ($member->isPublished() && $member->stagesDiffer()) {
                $member->doPublish();
            }
        }
    }
}
