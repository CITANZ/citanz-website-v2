<?php

namespace App\Web\Layout;

use Page;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use App\Web\Model\GenericBlock;
use App\Web\Model\CTATile;
use App\Web\Model\Team;
use SilverStripe\Versioned\Versioned;
use Leochenftw\Grid;
use Leochenftw\Util;
use Leochenftw\Util\CacheHandler;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class TeamPage extends Page
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'TeamPage';
    private static $description = 'This is the Team page. You can only have one Team page at any one time';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'BeliefTitle'   =>  'Varchar(128)',
        'TeamTitle'     =>  'Varchar(128)',
        'TeamExcerpt'   =>  'Text'
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'BeliefPoints'  =>  GenericBlock::class,
        'Teams'         =>  Team::class,
        'CTATiles'      =>  CTATile::class
    ];

    public function getData($mini = false)
    {
        if ($mini) {
            if ($mini_data = CacheHandler::read('page.' . $this->ID . '.mini', 'PageData')) {
                return $mini_data;
            }

            $mini_data  =   parent::getData($mini);

            CacheHandler::save('page.' . $this->ID . '.mini', $mini_data, 'PageData');

            return $mini_data;
        }

        if ($data = CacheHandler::read('page.' . $this->ID, 'PageData')) {
            return $data;
        }

        $data               =   parent::getData();

        unset($data['content']);

        $home               =   HomePage::get()->first();

        $data['home_data']  =   [
            'num_meetups'   =>  $home->NumMeetups,
            'num_attendees' =>  $home->NumAttendees,
            'content'       =>  Util::preprocess_content($this->Content),
            'sponsors'      =>  $home->Sponsors()->getData()
        ];

        $data['believes']   =   [
            'title'         =>  $this->BeliefTitle,
            'points'        =>  $this->BeliefPoints()->getData()
        ];

        $data['team']       =   [
            'title'         =>  $this->TeamTitle,
            'excerpt'       =>  str_replace("\n", '<br />', $this->TeamExcerpt),
            'teams'         =>  $this->Teams()->getData()
        ];

        $data['cta_tiles']  =   $this->CTATiles()->getData();

        CacheHandler::save('page.' . $this->ID, $data, 'PageData');
        return $data;
    }


    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.OurBebieves',
            [
                TextField::create('BeliefTitle', 'Title'),
                Grid::make('BeliefPoints', 'What we believe', $this->BeliefPoints())
            ]
        );

        $fields->addFieldsToTab(
            'Root.Teams',
            [
                TextField::create('TeamTitle', 'Title'),
                TextareaField::create('TeamExcerpt', 'Excerpt'),
                Grid::make('Teams', 'Teams', $this->Teams(), true, 'GridFieldConfig_RelationEditor')
            ]
        );

        $fields->addFieldsToTab(
            'Root.CallToActionTiles',
            [
                Grid::make('CTATiles', 'Call to action tiles', $this->CTATiles())
            ]
        );

        return $fields;
    }


    public function canCreate($member = null, $context = [])
    {
        if (Versioned::get_by_stage(__CLASS__, 'Stage')->count() > 0) {
            return false;
        }

        return parent::canCreate($member, $context);
    }
}
