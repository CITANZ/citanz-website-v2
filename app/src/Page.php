<?php

use App\Web\Page\HomePage;
use leochenftw\Util;
use Leochenftw\Util\CacheHandler;
use SilverStripe\CMS\Model\SiteTree;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\TextField;
use SilverStripe\Control\Controller;
use SilverStripe\Core\ClassInfo;
use SilverStripe\Core\Flushable;
use SilverStripe\Forms\FieldList;
use SilverStripe\SiteConfig\SiteConfig;
use SilverShop\HasOneField\HasOneButtonField;
use App\Web\Model\PageHero;

class Page extends SiteTree implements Flushable
{
    public static function flush()
    {
        CacheHandler::delete(null, 'PageData');
    }

    private static $db = [
        'Excerpt'   =>  'Text'
    ];

    private static $has_one = [
        'PageHero'  =>  PageHero::class
    ];

    /**
     * CMS Fields.
     *
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $this->extend('updateCMSFields', $fields);

        $meta = $fields->fieldbyName('Root.Main.Metadata');

        $fields->removeByName([
            'Metadata',
        ]);

        $fields->addFieldsToTab(
            'Root.Main',
            [
                TextareaField::create('Excerpt'),
                HasOneButtonField::create($this, "PageHero")
            ],
            'URLSegment'
        );

        if ($fields->fieldbyName('Root.SEO.OG')) {
            $fields->addFieldToTab(
                'Root.SEO',
                $meta,
                'OG'
            );
        }

        return $fields;
    }

    public function getData()
    {
        $siteconfig = SiteConfig::current_site_config();
        $data = [
            'id' => $this->ID,
            'pagetitle' => !empty($this->MetaTitle) ? $this->MetaTitle : $this->Title,
            'navigation' => $this->getMenuItems(),
            'title' => ($this instanceof HomePage) ? SiteConfig::current_site_config()->Title : (!empty($this->MetaTitle) ? $this->MetaTitle : $this->Title),
            'menu_title' => $this->MenuTitle,
            'content' => Util::preprocess_content($this->Content),
            'hero' => $this->PageHero()->exists() ? $this->PageHero()->Data : null,
            'pagetype' => ClassInfo::shortName($this->ClassName),
            'ancestors' => $this->get_ancestors($this),
            'excerpt' => $this->Excerpt,
        ];

        if (!empty($siteconfig->Data)) {
            $data = array_merge($data, ['siteconfig' => $siteconfig->Data]);
        }

        $this->extend('getData', $data);

        return $data;
    }

    /**
     * Event handler called after writing to the database.
     */
    protected function onAfterWrite()
    {
        parent::onAfterWrite();
        CacheHandler::delete(null, 'PageData');
    }

    private function get_ancestors($item, $ancestors = [])
    {
        if (!$item->Parent()->exists()) {
            return array_reverse($ancestors);
        }

        $ancestors[] = [
            'title' => $item->Parent()->Title,
            'link' => $item->Parent()->Link(),
        ];

        return $this->get_ancestors($item->Parent(), $ancestors);
    }

    private function getMenuItems($nav = null)
    {
        $controller = Controller::curr();
        $controller = !$controller->hasMethod('getMenu') ? PageController::create() : $controller;
        $nav = empty($nav) ? $controller->getMenu(1) : $nav;
        $list = [];
        foreach ($nav as $item) {
            $link = $item->Link();

            $list[] = [
                'label' => $item->MenuTitle,
                'url' => $link,
                'active' => $item->isSection() || $item->isCurrent(),
                'sub' => $this->getMenuItems($item->Children()),
                'pagetype' => ClassInfo::shortName($item->ClassName),
                'isExternal' => !empty($item->RedirectionType) ? $item->RedirectionType === 'External' : false,
            ];
        }

        return $list;
    }
}
