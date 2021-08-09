<?php

namespace App\Web\Layout;

use Page;
use SilverStripe\Forms\HTMLEditor\HtmlEditorField;
use SilverStripe\Forms\TextField;
use gorriecoe\LinkField\LinkField;
use gorriecoe\Link\Models\Link;
use SilverStripe\Versioned\Versioned;
use App\Web\Model\Company;
use App\Web\Model\Testimonial;
use App\Web\Model\IconedContentBlock;
use App\Web\Model\JobOpportunity;
use App\Web\Model\ProgrammeTile;
use App\web\Model\ImagedBlock;
use Cita\ImageCropper\Model\CitaCroppableImage;
use Cita\ImageCropper\Fields\CroppableImageField;
use Leochenftw\Grid;
use Leochenftw\Util;
use Leochenftw\Util\CacheHandler;
use SilverStripe\Control\Director;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class HomePage extends Page
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'WhereWeHereTitle'  =>  'Varchar(32)',
        'NumMeetups'        =>  'Varchar(16)',
        'NumAttendees'      =>  'Varchar(16)',
        'MissionTitle'      =>  'Varchar(128)',
        'MissionContent'    =>  'HTMLText',
        'BenefitTitle'      =>  'Varchar(128)',
        'BenefitContent'    =>  'HTMLText',
        'OpportunityTitle'  =>  'Varchar(128)',
        'OpportunityContent'=>  'HTMLText',
        'SponsorTitle'      =>  'Varchar(128)',
        'SponsorContent'    =>  'HTMLText',
        'UpcomingTitle'     =>  'Varchar(128)',
        'UpcomingContent'   =>  'HTMLText',
    ];

    private static $icon_class = 'font-icon-home';

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'WhyWeHereImage'    =>  CitaCroppableImage::class,
        'BenefitImage'      =>  CitaCroppableImage::class,
        'OpportunityImage'  =>  CitaCroppableImage::class
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'MissionPoints'         =>  'App\Web\Model\IconedContentBlock.MissionPointOf',
        'BenefitPoints'         =>  'App\Web\Model\IconedContentBlock.BenefitPointOf',
        'OpportunityPoints'     =>  'App\Web\Model\IconedContentBlock.OpportunityPointOf',
        'Testimonials'          =>  Testimonial::class,
        'UpcomingProgrammes'    =>  ProgrammeTile::class,
        'Jobs'                  =>  JobOpportunity::class,
        'CallToActionTiles'     =>  ImagedBlock::class,
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'MembersFromCompanies'  =>  Company::class,
        'Sponsors'              =>  Company::class
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

        if (Director::isLive()) {
            if ($data = CacheHandler::read('page.' . $this->ID, 'PageData')) {
                return $data;
            }
        }

        $data                   =   parent::getData();
        unset($data['content']);
        $data['why_we_here']    =   $this->prep_why_section();
        $data['connect']        =   $this->prep_connect_section();
        $data['grow']           =   $this->prep_grow_section();
        $data['explore']        =   $this->prep_explore_section();
        $data['image_cta']      =   $this->CallToActionTiles()->getData();
        $data['testimonials']   =   $this->Testimonials()->getData();
        $data['sponsors']       =   $this->prep_sponsor_section();
        $data['upcoming']       =   $this->prep_upcoming_section();

        CacheHandler::save('page.' . $this->ID, $data, 'PageData');

        return $data;
    }


    /**
     * Defines Database fields for the Many_many bridging table
     * @var array
     */
    private static $many_many_extraFields = [
        'MembersFromCompanies' => [
            'Sort' => 'Int'
        ],
        'Sponsors' => [
            'Sort' => 'Int'
        ]
    ];

    private function prep_upcoming_section()
    {
        return [
            'title'     =>  $this->UpcomingTitle,
            'content'   =>  Util::preprocess_content($this->UpcomingContent),
            'tiles'     =>  $this->UpcomingProgrammes()->getData(),
        ];
    }

    private function prep_sponsor_section()
    {
        return [
            'title'     =>  $this->SponsorTitle,
            'content'   =>  Util::preprocess_content($this->SponsorContent),
            'sponsors'  =>  $this->Sponsors()->getData()
        ];
    }

    private function prep_explore_section()
    {
        return [
            'title'     =>  $this->OpportunityTitle,
            'content'   =>  Util::preprocess_content($this->OpportunityContent),
            'image'     =>  $this->OpportunityImage()->exists() ?
                            $this->OpportunityImage()->getCropped()->getData('ScaleWidth', [366, 324, 588]) : null,
            'points'    =>  $this->OpportunityPoints()->getData(),
            'jobs'      =>  $this->Jobs()->getData()
        ];
    }

    private function prep_grow_section()
    {
        return [
            'title'     =>  $this->BenefitTitle,
            'content'   =>  Util::preprocess_content($this->BenefitContent),
            'image'     =>  $this->BenefitImage()->exists() ?
                            $this->BenefitImage()->getCropped()->getData('ScaleWidth', [366, 288, 500]) : null,
            'points'    =>  $this->BenefitPoints()->getData(),
        ];
    }

    private function prep_connect_section()
    {
        return [
            'title'     =>  $this->MissionTitle,
            'content'   =>  Util::preprocess_content($this->MissionContent),
            'points'    =>  $this->MissionPoints()->getData()
        ];
    }

    private function prep_why_section()
    {
        return [
            'title'         =>  $this->WhereWeHereTitle,
            'content'       =>  Util::preprocess_content($this->Content),
            'image'         =>  $this->WhyWeHereImage()->exists() ?
                                $this->WhyWeHereImage()->getCropped()->getData('ScaleWidth', [366, 324, 588]) : null,
            'num_meetups'   =>  $this->NumMeetups,
            'num_attendees' =>  $this->NumAttendees,
            'companies'     =>  $this->MembersFromCompanies()->getData()
        ];
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $this->setup_about_section($fields);
        $this->setup_mission_statement_section($fields);
        $this->setup_member_benefit_section($fields);
        $this->setup_career_opportunity_section($fields);

        $fields->addFieldToTab(
            'Root.Testimonials',
            Grid::make('Testimonials', 'Testimonials', $this->Testimonials())
        );

        $fields->addFieldToTab(
            'Root.CallToActionTiles',
            Grid::make('CallToActionTiles', 'Testimonials', $this->CallToActionTiles())
        );

        $this->setup_sponsors_section($fields);
        $this->setup_upcoming_section($fields);
        return $fields;
    }

    private function setup_career_opportunity_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.Explore',
            [
                TextField::create('OpportunityTitle', 'Title'),
                CroppableImageField::create('OpportunityImage', 'Image', $this)->setCropperRatio(588/376),
                HtmlEditorField::create('OpportunityContent', 'Content'),
                Grid::make('OpportunityPoints', 'Career opportunity key points', $this->OpportunityPoints(), true, 'GridFieldConfig_RelationEditor'),
                Grid::make('Jobs', 'Featured Jobs', $this->Jobs())
            ]
        );
    }

    private function setup_member_benefit_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.Grow',
            [
                TextField::create('BenefitTitle', 'Title'),
                CroppableImageField::create('BenefitImage', 'Image', $this)->setCropperRatio(500/608),
                HtmlEditorField::create('BenefitContent', 'Content'),
                Grid::make('BenefitPoints', 'Member benefit key points', $this->BenefitPoints(), true, 'GridFieldConfig_RelationEditor')
            ]
        );
    }

    private function setup_upcoming_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.UpcomingProgrammes',
            [
                TextField::create('UpcomingTitle', 'Title'),
                HtmlEditorField::create('UpcomingContent', 'Content'),
                Grid::make('UpcomingProgrammes', 'Upcoming Programmes', $this->UpcomingProgrammes())
            ]
        );
    }

    private function setup_sponsors_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.Sponsors',
            [
                TextField::create('SponsorTitle', 'Title'),
                HtmlEditorField::create('SponsorContent', 'Content'),
                Grid::make('Sponsors', 'Our Sponsors', $this->Sponsors(), true, 'GridFieldConfig_RelationEditor')
            ]
        );
    }


    private function setup_mission_statement_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.Connect',
            [
                TextField::create('MissionTitle', 'Title'),
                HtmlEditorField::create('MissionContent', 'Content'),
                Grid::make('MissionPoints', 'Missoin statement key points', $this->MissionPoints(), true, 'GridFieldConfig_RelationEditor')
            ]
        );
    }

    private function setup_about_section(&$fields)
    {
        $fields->addFieldsToTab(
            'Root.WhyWeHere',
            [
                TextField::create('WhereWeHereTitle', 'Title'),
                $fields->fieldByName('Root.Main.Content'),
                // LinkField::create('WhyWeHereReadMoreLink', 'Read more link', $this),
                CroppableImageField::create('WhyWeHereImage', 'Image', $this)->setCropperRatio(1142/816),
                TextField::create('NumMeetups', 'Number of Meetups'),
                TextField::create('NumAttendees', 'Number of Attendees'),
                Grid::make('MembersFromCompanies', 'Our members are from', $this->MembersFromCompanies(), true, 'GridFieldConfig_RelationEditor')
            ]
        );
    }

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'HomePage';
    private static $description = 'This is the Homepage. You can only have one Homepage at any one time';

    public function canCreate($member = null, $context = [])
    {
        if (Versioned::get_by_stage(__CLASS__, 'Stage')->count() > 0) {
            return false;
        }

        return parent::canCreate($member, $context);
    }
}
