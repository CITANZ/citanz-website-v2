<?php

namespace App\Web\Model;

use SilverStripe\ORM\DataObject;
use App\Web\Layout\CertificatesPage;
use Leochenftw\Extension\SortOrderExtension;
use Leochenftw\Util;

class Certificate extends DataObject implements \JsonSerializable
{
    private static $table_name = 'Certificate';

    private static $db = [
        'Title' => 'Varchar(128)',
        'Level' => 'Varchar(128)',
        'Content' => 'HTMLText',
    ];

    private static $has_one = [
        'Company' => Company::class,
        'Page' => CertificatesPage::class,
    ];

    private static $extensions = [
        SortOrderExtension::class
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName(['PageID']);

        return $fields;
    }

    public function getData()
    {
        return $this;
    }

    public function jsonSerialize() {
        return [
            'id' => 'cert-id-' . $this->ID,
            'title' => $this->Title,
            'level' => $this->Level,
            'content' => Util::preprocess_content($this->Content),
        ];
    }
}
