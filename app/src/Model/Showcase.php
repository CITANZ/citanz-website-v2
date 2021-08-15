<?php

namespace App\Web\Model;

use SilverStripe\Assets\Image;
use SilverStripe\ORM\DataObject;
use Leochenftw\Extension\SortOrderExtension;
use App\Web\Layout\ShowcasePage;

class Showcase extends DataObject
{
    private static $table_name = 'Showcase';

    private static $db = [
        'Title' => 'Varchar(128)',
        'Credit' => 'Varchar(128)',
        'Date' => 'Date',
    ];

    private static $has_one = [
        'Page' => ShowcasePage::class,
        'Image' => Image::class,
    ];

    private static $extensions = [
        SortOrderExtension::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'PageID',
        ]);

        return $fields;
    }
}
