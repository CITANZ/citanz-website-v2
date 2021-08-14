<?php

namespace App\Web\Extension;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use gorriecoe\LinkField\LinkField;
use gorriecoe\Link\Models\Link;
use SilverStripe\Forms\EmailField;
use SilverStripe\AssetAdmin\Forms\UploadField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Assets\File;
use SilverStripe\ORM\DataExtension;
use SilverStripe\Forms\LiteralField;
use Leochenftw\Grid;
use Page;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class SiteConfigExtension extends DataExtension
{
    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Email'                 =>  'Varchar(256)',
        'ProfilesFacebookPage'  =>  'Varchar(512)',
        'ProfilesTwitterPage'   =>  'Varchar(512)',
        'ProfilesGooglePage'    =>  'Varchar(512)',
        'ProfilesLinkedinPage'  =>  'Varchar(512)',
        'ProfilesPinterestPage' =>  'Varchar(512)',
        'ProfilesYoutubePage'   =>  'Varchar(512)',
        'ProfilesInstagramPage' =>  'Varchar(512)',
        'SubscriptionTitle'     =>  'Varchar(128)',
        'SubscriptionContent'   =>  'Text',
        'MailchimpEndpoint'     =>  'Varchar(128)',
        'MailchimpListID'       =>  'Varchar(32)',
        'MailchimpAPIKey'       =>  'Varchar(256)',
        'MailchimpURL'          =>  'Varchar(256)',
        'ShowNotification'      =>  'Boolean',
        'NotificationContent'   =>  'Text'
    ];

    /**
     * Many_many relationship
     * @var array
     */
    private static $many_many = [
        'FooterMenu'    =>  Page::class
    ];

    public function get_mailchimp_config()
    {
        return [
            'endpoint'      =>  rtrim($this->owner->MailchimpEndpoint, '/') . '/' . $this->owner->MailchimpListID . '/members',
            'api_key'       =>  $this->owner->MailchimpAPIKey,
            'fallback_url'  =>  $this->owner->MailchimpURL
        ];
    }

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'QRCode'            =>  File::class,
        'SponsorCTA'        =>  Link::class,
        'DonateCTA'         =>  Link::class,
        'NotificationLink'  =>  Link::class
    ];

    public function getData()
    {
        return [
            'logo'          =>  $this->owner->Logo()->Data,
            'title'         =>  $this->owner->Title,
            'tagline'       =>  $this->owner->Tagline,
            'notification'  =>  $this->owner->ShowNotification ?
                                [
                                    'link'      =>  $this->owner->NotificationLink()->getData(),
                                    'message'   =>  $this->owner->NotificationContent
                                ] : null,
            'cta'           =>  [
                'sponsor_us'    =>  $this->owner->SponsorCTA()->getData(),
                'donate'        =>  $this->owner->DonateCTA()->exists() ?
                                    $this->owner->DonateCTA()->getData() : null
            ],
            'menu'          =>  $this->prep_footer_menu(),
            'contact'       =>  [
                'email'     =>  $this->owner->Email,
                'socials'   =>  $this->prep_social_medias(),
                'wechat_qr' =>  $this->owner->QRCode()->getData()
            ],
            'mailchimp'     =>  [
                'title'     =>  $this->owner->SubscriptionTitle,
                'content'   =>  str_replace("\n", "<br />", $this->owner->SubscriptionContent),
                'config'    =>  $this->owner->get_mailchimp_config()
            ]
        ];
    }

    private function prep_social_medias()
    {
        $data = [
            'facebook'      =>  !empty($this->owner->ProfilesFacebookPage) ?
                                [
                                    'icon'  =>  'facebook',
                                    'url'   =>  $this->owner->ProfilesFacebookPage
                                ] : null,
            'twitter'       =>  !empty($this->owner->ProfilesTwitterPage) ?
                                [
                                    'icon'  =>  'twitter',
                                    'url'   =>  $this->owner->ProfilesTwitterPage
                                ] : null,
            'google'        =>  !empty($this->owner->ProfilesGooglePage) ?
                                [
                                    'icon'  =>  'google-plus',
                                    'url'   =>  $this->owner->ProfilesGooglePage
                                ] : null,
            'linkedin'      =>  !empty($this->owner->ProfilesLinkedinPage) ?
                                [
                                    'icon'  =>  'linkedin',
                                    'url'   =>  $this->owner->ProfilesLinkedinPage
                                ] : null,
            'pinterest'     =>  !empty($this->owner->ProfilesPinterestPage) ?
                                [
                                    'icon'  =>  'pinterest',
                                    'url'   =>  $this->owner->ProfilesPinterestPage
                                ] : null,
            'youtube'       =>  !empty($this->owner->ProfilesYoutubePage) ?
                                [
                                    'icon'  =>  'youtube',
                                    'url'   =>  $this->owner->ProfilesYoutubePage
                                ] : null,
            'instagram'     =>  !empty($this->owner->ProfilesInstagramPage) ?
                                [
                                    'icon'  =>  'instagram',
                                    'url'   =>  $this->owner->ProfilesInstagramPage
                                ] : null
        ];

        return array_filter($data, function($item) {
            return !empty($item);
        });
    }

    private function prep_footer_menu()
    {
        $menu   =   [];

        foreach ($this->owner->FooterMenu() as $item) {
            $link   =   $item->Link();
            $menu[] =   [
                'title'     =>  $item->MenuTitle,
                'url'       =>  $link != '/' ? rtrim($link, '/') : '/',
            ];
        }

        return $menu;
    }

    /**
     * Relationship version ownership
     * @var array
     */
    private static $owns = [
        'QRCode'
    ];

    /**
     * Update Fields
     * @return FieldList
     */
    public function updateCMSFields(FieldList $fields)
    {
        $owner = $this->owner;
        $fields->addFieldsToTab(
            'Root.Main',
            [
                EmailField::create('Email', 'Contact Email')
            ]
        );

        $fields->addFieldsToTab(
            'Root.Notification',
            [
                LinkField::create('NotificationLink', 'Notification Link', $this->owner),
                TextField::create('NotificationContent', 'Notification Content'),
                CheckboxField::create('ShowNotification', 'Display the notification message on the top of the frontend pages')
            ]
        );

        $fields->addFieldsToTab(
            'Root.FooterMenu',
            [
                Grid::make('FooterMenu', 'Footer Menu', $this->owner->FooterMenu(), false, 'GridFieldConfig_RelationEditor'),
                LinkField::create(
                    'SponsorCTA',
                    'Sponsor us',
                    $this->owner
                ),
                LinkField::create(
                    'DonateCTA',
                    'Donate',
                    $this->owner
                )
            ]
        );

        $fields->addFieldsToTab(
            'Root.SubscriptionSettings',
            [
                TextField::create('SubscriptionTitle', 'Title'),
                TextareaField::create('SubscriptionContent', 'Content'),
                TextField::create('MailchimpEndpoint', 'Mailchimp endpoint'),
                TextField::create('MailchimpListID', 'Mailchimp subscriber list ID'),
                TextField::create('MailchimpAPIKey', 'Mailchimp API key'),
                TextField::create(
                    'MailchimpURL',
                    'Mailchimp subscription form URL'
                )->setDescription('This is a fallback URL when MC API plays up (click <a href="https://stackoverflow.com/questions/52198510/mailchimp-resubscribe-a-deleted-member-causes-the-api-to-return-a-400-bad-reques?rq=1" target="_blank">here</a> to read the stupid reason why)'),
            ]
        );

        $fields->addFieldsToTab(
            'Root.SocialMediaProfiles',
            [
                UploadField::create(
                    'QRCode',
                    'QRCode'
                ),
                TextField::create('ProfilesFacebookPage', 'Facebook'),
                TextField::create('ProfilesTwitterPage', 'Twitter'),
                TextField::create('ProfilesGooglePage', 'Google'),
                TextField::create('ProfilesLinkedinPage', 'Linkedin'),
                TextField::create('ProfilesPinterestPage', 'Pinterest'),
                TextField::create('ProfilesYoutubePage', 'Youtube'),
                TextField::create('ProfilesInstagramPage', 'Instagram'),
            ]
        );

        $fields->removeByName([
            'Logo'
        ]);

        return $fields;
    }

    private function updateLinkURL($url)
    {
        if($url) {
            if(strpos('https://', $url) !== 0 || strpos('http://', $url) !== 0) {
                $url = 'http://' . $url;
            }
        }
        return $url;
    }
}
