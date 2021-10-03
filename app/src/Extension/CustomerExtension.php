<?php

namespace App\Web\Extension;

use SilverStripe\Dev\Debug;
use SilverStripe\Assets\Image;
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
use App\Web\Model\StudentDiscountApplication;
use SilverStripe\SiteConfig\SiteConfig;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class CustomerExtension extends DataExtension
{
    private static $db = [
        'CitaID' => 'Varchar',
        'PendingEmail' => 'Varchar(256)',
        'Dob' => 'Date',
        'Gender' => 'Enum("Female,Male,Other")',
        'isStudent' => 'Boolean',
        'Organisation' => 'Varchar',
        'Degree' => 'Enum("Certificate,Diploma,Bachelor,Post-graduate,PhD")',
        'Major' => 'Varchar',
        'TitleLevel' => 'Enum("Entry,Junior,Intermedia,Senior,Lead")',
        'JobCategory' => 'Varchar',
        'WechatID' => 'Varchar',
        'LinkedInLink' => 'Varchar(1024)',
        'Github' => 'Varchar',
        'Expiry30Reminded' => 'Boolean', // 30 days prior to expiry
        'Expiry7Reminded' => 'Boolean', // 7 days prior to expiry
        'Expiry0Reminded' => 'Boolean', // on the expiry date
    ];

    private static $default_sort = ['CitaID' => 'ASC'];

    private static $defaults = [
        'Gender' => null,
        'Degree' => null,
        'TitleLevel' => null,
        'Expiry30Reminded' => true,
        'Expiry7Reminded' => true,
        'Expiry0Reminded' => true,
    ];

    private static $summary_fields = [
        'CitaID' => 'Member ID',
    ];

    private static $has_many = [
        'StudentDiscountApplications' => StudentDiscountApplication::class,
    ];

    public function usedToBeAMember()
    {
        return $this->owner->LastSubscription && !$this->owner->isValidMembership();
    }

    public function updateCMSFields(FieldList $fields)
    {
        $fields->fieldByName('Root.Main.Degree')->setEmptyString('- select one -');
        return $fields;
    }

    public function getExtraCustomerData()
    {
        return [
            'citaID' => $this->owner->CitaID,
            'neverExpire' => $this->owner->NeverExpire ? true : false,
            'canRenew' => $this->owner->canRenew(),
            'isPaidMember' => $this->owner->isValidMembership(),
            'expiry' => date('d/m/Y', strtotime($this->owner->Expiry)),
            'usedToBeAMember' => $this->owner->usedToBeAMember(),
            'isStudent' => $this->owner->isStudent ? true : false,
            'isRealStudent' => $this->owner->isRealStudent(),
            'addressMissing' => $this->owner->Addresses()->first() ? empty($this->owner->Addresses()->first()->Address) : true,
            'hasPendingStudentApplication' => $this->owner->StudentDiscountApplications()->filter(['Approved' => false, 'Rejected' => false])->exists(),
        ];
    }

    public function updateAddress($data)
    {
        if (!$this->owner->Addresses()->exists()) {
            $this->owner->createAddressHolder();
        }

        $address = $this->owner->Addresses()->first();
        $address->update($data)->write();
    }

    public function getFullProfile()
    {
        return [
            'dob' => $this->owner->Dob,
            'gender' => $this->owner->Gender,
            'address' => $this->owner->Addresses()->first(),
            'organisation' => $this->owner->Organisation,
            'degree' => $this->owner->Degree,
            'major' => $this->owner->Major,
            'titleLevel' => $this->owner->TitleLevel,
            'jobCategory' => $this->owner->JobCategory,
            'wechatID' => $this->owner->WechatID,
            'linkedinLink' => $this->owner->LinkedInLink,
            'github' => $this->owner->Github,
        ];
    }

    public function isRealStudent()
    {
        return $this->owner->Groups()->filter(['Title:nocase' => 'student members'])->exists();
    }

    public function syncToMailchimp()
    {
        $config     =   SiteConfig::current_site_config()->MailChimpMemberConfig;
        $endpoint   =   $config['endpoint'];
        $key        =   $config['api_key'];

        try {
            $client     =   new Client();
            $response   =   $client->request(
                'POST',
                $endpoint,
                [
                    'headers'   =>  [
                        'Authorization' => "apikey {$key}"
                    ],
                    'json'   =>  [
                        'email_address' =>  $this->owner->Email,
                        'status'        =>  'subscribed',
                        'merge_fields'  =>  [
                            "FNAME" =>  $this->owner->FirstName,
                            "LNAME" =>  $this->owner->LastName,
                        ]
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents());
        } catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody()->getContents());
        }
    }

    public function updateMailchimpPaidTag()
    {
        $config     =   SiteConfig::current_site_config()->MailChimpMemberConfig;
        $endpoint   =   $config['endpoint'] . '/' . $this->owner->Email . '/tags';
        $key        =   $config['api_key'];

        $isPaid     = strtotime($this->owner->Expiry) > time() || $this->owner->NeverExpire;

        try {
            $client     =   new Client();
            $response   =   $client->request(
                'POST',
                $endpoint,
                [
                    'headers'   =>  [
                        'Authorization' => "apikey {$key}"
                    ],
                    'json' => [
                        'tags' => [
                            [
                                'name' => 'Paid members',
                                'status' => $isPaid ? 'active' : 'inactive',
                            ]
                        ],
                    ]
                ]
            );

            return json_decode($response->getBody()->getContents());
        } catch (ClientException $e) {
            return json_decode($e->getResponse()->getBody()->getContents());
        }
    }
}
