<?php

namespace App\Web\Layout;

use App\Web\Model\Showcase;
use Page;
use Leochenftw\Grid;

class ShowcasePage extends Page
{
    private static $table_name = 'ShowcasePage';

    private static $has_many = [
        'Showcases' => Showcase::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Showcases',
            [
                Grid::make('Showcases', 'Showcases', $this->Showcases(), true, 'GridFieldConfig_RelationEditor')
            ]
        );
        return $fields;
    }
}
