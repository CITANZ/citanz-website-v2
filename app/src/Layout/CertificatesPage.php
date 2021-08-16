<?php

namespace App\Web\Layout;

use App\Web\Model\Certificate;
use Page;
use Leochenftw\Grid;

class CertificatesPage extends Page
{
    private static $table_name = 'CertificatesPage';

    private static $has_many = [
        'Certificates' => Certificate::class,
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->addFieldsToTab(
            'Root.Certifcates',
            [
                Grid::make('Certificates', 'Certificates', $this->Certificates(), true, 'GridFieldConfig_RelationEditor'),
            ]
        );

        return $fields;
    }

    public function getData()
    {
        return array_merge(
            parent::getData(),
            [
                'certificates' => array_map(function($cert) {
                    return $cert;
                }, $this->Certificates()->toArray()),
            ]
        );
    }
}
