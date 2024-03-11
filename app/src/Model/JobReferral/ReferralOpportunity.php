<?php

namespace App\Web\JobReferral\Model;

use Cita\eCommerce\Model\Customer;
use SilverStripe\ORM\DataObject;
use App\Web\Model\Company;
use SilverStripe\View\Parsers\ShortcodeParser;

class ReferralOpportunity extends DataObject implements \JsonSerializable
{
    /**
     * Defines the database table name
     *  @var string
     */
    private static string $table_name = 'ReferralOpportunity';

    /**
     * Default sort ordering
     * @var string
     */
    private static string $default_sort = 'Created DESC';

    private static array $db = [
        'JobTitle' => 'Varchar(128)',
        'JobDescription' => 'HTMLText',
        'FAQs' => 'Text',
        'ValidUntil' => 'Datetime',
        'TimesViewed' => 'Int',
        'WorkLocation' => 'Varchar(128)',
        'WTR' => 'Boolean',
        'Deleted' => 'Boolean',
    ];

    /**
     * Defines a default list of filters for the search context
     * @var array
     */
    private static $searchable_fields = [
        'JobTitle',
        'Deleted',
    ];

    private static array $has_one = [
        'Company' => Company::class,
        'PostedBy' => Customer::class,
    ];

    /**
     * Has_many relationship
     * @var array
     */
    private static $has_many = [
        'Applications' => JobApplication::class,
    ];

    private static $cascade_deletes = [
        'Applications',
    ];

    public function getIsAppliable()
    {
        $validUntil = ctype_digit($this->ValidUntil) ? $this->ValidUntil : strtotime($this->ValidUntil);
        return !$this->Deleted && $validUntil >= time();
    }

    public function getTitle()
    {
        return $this->Company()->Title . ' - ' . $this->JobTitle;
    }

    public function validate() 
    {
        $result = parent::validate();

        if (empty($this->CompanyID)) {
            $result->addError('Please provide the company / entity that is hiring!');
        }

        return $result;
    }

    public function jsonSerialize() {
        return array_merge(
            $this->TileData,
            [
                'description' => ShortcodeParser::get_active()->parse($this->JobDescription),
                'faqs' => nl2br(trim($this->FAQs)),
            ]
        );
    }

    public function getTileData()
    {
        return [
            'id' => 'referral-opportunity-' . $this->ID,
            'title' => $this->Title,
            'rawTitle' => $this->JobTitle,
            'until' => strtotime($this->ValidUntil) * 1000,
            'company' => $this->Company()->exists() ? $this->Company()->Data : null,
            'viewCount' => $this->TimesViewed,
            'appliedCount' => $this->Applications()->count(),
            'workLocation' => $this->WorkLocation,
            'postedBy' => $this->PostedBy()->exists() ? $this->PostedBy()->Fullname : 'Anonymous member',
            'wtr' => (bool) $this->WTR,
        ];
    }

    public function getTileDataPublic()
    {
        return [
            'id' => 'referral-opportunity-' . $this->ID,
            'rawTitle' => $this->JobTitle,
            'until' =>  date('d/m/Y', strtotime($this->ValidUntil)),
            'company' => $this->Company()->exists() ? $this->Company()->Data : null,
            'workLocation' => empty(trim($this->WorkLocation)) ? '<em>TBC</em>' : $this->WorkLocation,
            'postedBy' => $this->PostedBy()->exists() ? $this->PostedBy()->Fullname : 'Anonymous member',
            'wtr' => (bool) $this->WTR,
        ];
    }

    public function getData()
    {
        return $this->jsonSerialize();
    }
}