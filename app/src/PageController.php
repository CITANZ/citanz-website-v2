<?php

use Leochenftw\Restful\RestfulController;
use Leochenftw\Util;
use SilverStripe\CMS\Controllers\ContentController;
use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use SilverStripe\Core\Convert;
use SilverStripe\ErrorPage\ErrorPage;
use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\View\ArrayData;
use SilverStripe\View\Requirements;

class PageController extends ContentController
{
    private static $allowed_actions = [];

    public function MetaTags($includeTitle = true)
    {
        $tags = '';

        if ($this->ConanicalURL) {
            $tags .= '<link rel="canonical" href="' . Convert::raw2att($this->ConanicalURL) . "\" />\n";
        }

        if ($this->MetaKeywords) {
            $tags .= '<meta name="keywords" content="' . Convert::raw2att($this->MetaKeywords) . "\" />\n";
        }

        if ($this->MetaDescription) {
            $tags .= '<meta name="description" content="' . Convert::raw2att($this->MetaDescription) . "\" />\n";
        } elseif (!empty($this->Content)) {
            $trimmed = substr(strip_tags(Util::preprocess_content($this->Content)), 0, 150) . (strlen(Util::preprocess_content($this->Content)) > 150 ? '...' : '');
            $tags .= '<meta name="description" content="' . Convert::raw2att($trimmed) . "\" />\n";
        }

        if ($this->ExtraMeta) {
            $tags .= $this->ExtraMeta . "\n";
        }

        if ('home' == $this->URLSegment && SiteConfig::current_site_config()->GoogleSiteVerificationCode) {
            $tags .= '<meta name="google-site-verification" content="'
                        . SiteConfig::current_site_config()->GoogleSiteVerificationCode . '" />' . "\n";
        }

        // prevent bots from spidering the site whilest in dev.
        if (!Director::isLive()) {
            $tags .= "<meta name=\"robots\" content=\"noindex, nofollow, noarchive\" />\n";
        } elseif (!empty($this->MetaRobots)) {
            $tags .= "<meta name=\"robots\" content=\"{$this->MetaRobots}\" />\n";
        } else {
            $tags .= "<meta name=\"robots\" content=\"INDEX, FOLLOW\" />\n";
        }

        $this->extend('MetaTags', $tags);

        return $tags;
    }

    public function getOGTwitter()
    {
        $site_config = SiteConfig::current_site_config();
        if (!empty($this->OGType) || !empty($site_config->OGType)) {
            $data = [
                'OGType' => !empty($this->OGType) ?
                    $this->OGType :
                    $site_config->OGType,
                'AbsoluteLink' => $this->AbsoluteLink(),
                'OGTitle' => (!empty($this->OGTitle) ?
                    $this->OGTitle :
                    $this->Title) . " - {$site_config->Title}",
                'OGDescription' => !empty($this->OGDescription) ?
                    $this->OGDescription :
                    $site_config->OGDescription,
                'OGImage' => !empty($this->OGImage()->exists()) ?
                    $this->OGImage() :
                    $site_config->OGImage(),
                'OGImageLarge' => !empty($this->OGImageLarge()->exists()) ?
                    $this->OGImageLarge() :
                    $site_config->OGImageLarge(),
                'TwitterCard' => !empty($this->TwitterCard) ?
                    $this->TwitterCard :
                    $site_config->TwitterCard,
                'TwitterTitle' => (!empty($this->TwitterTitle) ?
                    $this->TwitterTitle :
                    $this->Title) . " - {$site_config->Title}",
                'TwitterDescription' => !empty($this->TwitterDescription) ?
                    $this->TwitterDescription :
                    $site_config->TwitterDescription,
                'TwitterImageLarge' => !empty($this->TwitterImageLarge()->exists()) ?
                    $this->TwitterImageLarge() :
                    $site_config->TwitterImageLarge(),
                'TwitterImage' => !empty($this->TwitterImage()->exists()) ?
                    $this->TwitterImage() :
                    $site_config->TwitterImage(),
            ];

            return ArrayData::create($data);
        }

        return null;
    }

    public function isAdmin()
    {
        return Member::currentUser() && Member::currentUser()->inGroup('administrators');
    }

    public function getYear()
    {
        return date('Y', time());
    }

    public function getInitialPageData()
    {
        $data = $this->Data;

        if ($member = Member::currentUser()) {
            if ($member->inGroup('administrators') && $this->exists()) {
                return json_encode(array_merge($data, ['edit_url' => $this->CMSEditLink()]));
            }
        }

        return json_encode($data);
    }

    protected function handleAction($request, $action)
    {
        if (!$this->request->isAjax()) {
            return parent::handleAction($request, $action);
        }

        if (SiteConfig::current_site_config()->UnderMaintenance && !$this->isAdmin()) {
            $this->getResponse()->setStatusCode(503, 'Under maintenance');

            $error_page = ErrorPage::get()->filter(['ErrorCode' => 503])->first();

            if (!$error_page) {
                $error_page = Page::create();
                $error_page->Title = 'Under Maintenance';
                $error_page->Content = '<p>' . SiteConfig::current_site_config()->Title . ' is under maintenance. Please check back later.</p>';
            }

            return json_encode($error_page->Data);
        }

        if ($this->isAdmin() && $this->exists()) {
            return json_encode(array_merge($this->Data, ['edit_url' => $this->CMSEditLink()]));
        }

        return json_encode($this->Data);
    }

    protected function init()
    {
        parent::init();

        Requirements::css('leochenftw/leoss4bk: client/dist/vendor.css');
        Requirements::css('leochenftw/leoss4bk: client/dist/app.css');
        Requirements::javascript('leochenftw/leoss4bk: client/dist/vendor.js');
        Requirements::javascript('leochenftw/leoss4bk: client/dist/app.js');
    }

    protected function addCORSHeaders($response)
    {
        if (Director::isDev()) {
            $config = Config::inst()->get(RestfulController::class);

            $response->addHeader('Access-Control-Allow-Origin', $this->request->getHeader('origin'));
            $response->addHeader('Access-Control-Allow-Methods', $config['CORSMethods']);
            $response->addHeader('Access-Control-Max-Age', $config['CORSMaxAge']);
            $response->addHeader('Access-Control-Allow-Headers', $config['CORSAllowHeaders']);
            if ($config['CORSAllowCredentials']) {
                $response->addHeader('Access-Control-Allow-Credentials', 'true');
            }
        }

        $response->addHeader('Content-Type', 'application/json');

        return $response;
    }
}
