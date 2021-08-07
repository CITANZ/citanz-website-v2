<?php

namespace App\Web\Model;

use gorriecoe\LinkField\LinkField;
use gorriecoe\Link\Models\Link;
use App\Web\Layout\TeamPage;
use Page;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class CTATile extends GenericBlock
{
    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'CTATile';

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'ShowSponsors'  =>  'Boolean'
    ];

    /**
     * Has_one relationship
     * @var array
     */
    private static $has_one = [
        'Link'      =>  Link::class,
        'inPage'    =>  Page::class
    ];

    public function getData()
    {
        $data                   =   parent::getData();
        $data['show_sponsor']   =   $this->ShowSponsors ? true : false;
        $data['link']           =   $this->Link()->exists() ?
                                    $this->Link()->getData() : null;

        return $data;
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'LinkID',
            'inPageID'
        ]);
        $fields->addFieldToTab(
            'Root.Main',
            LinkField::create(
                'Link',
                'Call to action button',
                $this
            )
        );
        return $fields;
    }
}
