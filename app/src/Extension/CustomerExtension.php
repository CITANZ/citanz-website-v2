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
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Config\Config;
use SilverStripe\Control\Director;
use Leochenftw\Util;
use App\Web\JobReferral\Model\JobApplication;
use App\Web\JobReferral\Model\ReferralOpportunity;
use SilverStripe\Core\Environment;

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
        'CanCreateReferralOpportunities' => 'Boolean',
        'Expiry30Reminded' => 'Boolean', // 30 days prior to expiry
        'Expiry7Reminded' => 'Boolean', // 7 days prior to expiry
        'Expiry0Reminded' => 'Boolean', // on the expiry date
        'CV' => 'Varchar(1024)',
        'CoverLetter' => 'Varchar(1024)',
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

    private static $searchable_fields = [
        'CitaID',
        'FirstName',
        'LastName',
        'Email',
    ];

    private static $summary_fields = [
        'CitaID' => 'Member ID',
        'Expiry' => 'Expiry date',
        'LastSubscriptionPaymentDate' => 'Last Payment Date',
        'MyRegion' => 'Region',
        'MyCity' => 'City',
    ];

    private static $has_many = [
        'StudentDiscountApplications' => StudentDiscountApplication::class,
        'JobApplications' => JobApplication::class,
        'ListedJobs' => ReferralOpportunity::class,
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

    public function getMyRegion()
    {
        if ($this->owner->Addresses()->exists()) {
            return $this->owner->Addresses()->First()->Region ?? 'N/A';
        }

        return 'N/A';
    }

    public function getMyCity()
    {
        if ($this->owner->Addresses()->exists()) {
            return $this->owner->Addresses()->First()->City ?? 'N/A';
        }

        return 'N/A';
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
            'canListJob' => (bool) $this->owner->CanCreateReferralOpportunities,
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
        $s3BaseUrl = Environment::getEnv('AWS_S3_ASSETS_BASE_URL') ?? '';
        $cv = $this->owner->CV;
        $cl = $this->owner->CoverLetter;
        $guid = $this->owner->GUID;
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
            'canListJob' => (bool) $this->owner->CanCreateReferralOpportunities,
            'cv' => !empty($cv) ? "{$s3BaseUrl}{$guid}/{$cv}" : null,
            'cl' => !empty($cl) ? "{$s3BaseUrl}{$guid}/{$cl}" : null,
        ];
    }

    public function getCanViewListing()
    {
        return $this->owner->CanCreateReferralOpportunities
            || $this->owner->isValidMembership()
            || $this->owner->usedToBeAMember()
        ;
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
        $expiry = is_int($this->owner->Expiry) ? $this->owner->Expiry : strtotime($this->owner->Expiry);

        $isPaid     = $expiry > time() || $this->owner->NeverExpire;

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

    public function sendMemberInductionKit()
    {
        $from       = Config::inst()->get(Email::class, 'noreply_email');
        $to         = $this->owner->Email;
        $subject    = 'Welcome to CITANZ - Your membership Induction Kit';
        $siteconfig = SiteConfig::current_site_config();

        $email = Email::create($from, $to, $subject);

        if (Director::isLive()) {
            $email->setBCC(Config::inst()->get(Email::class, 'admin_email'));
        }

        $email->setHTMLTemplate('Email\\InductionKit');

        $content = Util::preprocess_content($siteconfig->InductionKitContent);

        $content = str_replace('[MemberName]', $this->owner->FirstName, $content);
        $content = str_replace('[MemberID]', $this->owner->CitaID, $content);

        $email->setData([
            'Content'    =>  $content,
            'WebsiteURL' => Director::absoluteURL(Director::baseURL()),
            'Year' => date('Y', time()),
        ]);

        $attachment = $siteconfig->InductionKit()->exists() ? $siteconfig->InductionKit() : null;

        if (!empty($attachment)) {
            $email->addAttachmentFromData($attachment->getString(), $attachment->Title, $attachment->getMimeType());
        }

        $email->send();
    }

    public function getFullname()
    {
        $isChinese = !(ctype_alpha($this->owner->FirstName) || ctype_alpha($this->owner->LastName));

        return $isChinese ? "{$this->owner->LastName}{$this->owner->FirstName}" : trim("{$this->owner->FirstName} {$this->owner->LastName}");
    }
}
