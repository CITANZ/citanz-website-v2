<?php

namespace App\Web\Model;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Dev\Debug;
use SilverStripe\ORM\DataObject;
use Leochenftw\Grid;
use Leochenftw\Extension\SortOrderExtension;
use SilverStripe\Forms\GridField\GridFieldDetailForm;
use Symbiote\GridFieldExtensions\GridFieldEditableColumns;
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
        'SortOrder' => 'Int',
        'JobTitle' => 'Varchar(128)',
      ]
    ];

    public function getData()
    {
        $me = $this;
        return [
            'title'     =>  $this->Title,
            'members'   =>  array_map(function($member) use($me) {
                return array_merge(
                    $member->Data,
                    [
                        'jobtitle' => $me->Members()->byID($member->ID)->JobTitle,
                    ]
                );
            }, $this->Members()->sort('SortOrder ASC')->toArray()),
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


            $grid = Grid::make('Members', 'Members', $this->Members(), true, 'GridFieldConfig_RelationEditor', false, 'SortOrder');

            $config = $grid->getConfig();

            $featuresGridFieldEditableColumns = new GridFieldEditableColumns();
            $featuresGridFieldEditableColumns->setDisplayFields([
                'Title' => [
                    'title' => 'Title',
                    'field' => ReadonlyField::class,
                ],
                'JobTitle' => [
                    'title' => 'Data',
                    'field' => TextField::class,
                ],
            ]);

            $config->addComponent($featuresGridFieldEditableColumns);

            $detailFormComponent = $config->getComponentByType(GridFieldDetailForm::class);

            $detailFormComponent->setItemEditFormCallback(function($form, $itemrequest) {
                $record = $itemrequest->record;
                $jbField = TextField::create('ManyMany[JobTitle]', 'Job title', $record->JobTitle);
                $form->Fields()->insertBefore('Linkedin', $jbField);
            });

            $fields->addFieldToTab(
                'Root.Main',
                $grid
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
