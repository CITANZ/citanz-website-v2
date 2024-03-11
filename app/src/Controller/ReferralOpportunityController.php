<?php

namespace App\Web\Layout;

use SilverStripe\Security\Member;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Dev\Debug;
use PageController;
use Leochenftw\Util;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use App\Web\Traits\OAuthTrait;
use App\Web\JobReferral\Model\ReferralOpportunity;
use Page;

class ReferralOpportunityController extends PageController
{
    use OAuthTrait;

    const PAGE_SIZE = 10;

    private $job = null;

    private static $allowed_actions = [
        'doApply' => true
    ];

    private static $url_handlers = [
        'apply' => 'doApply'
    ];

    public function doInit()
    {
        parent::doInit();
        
        if (isset($_COOKIE['accesstoken'])) {
            $this->user = $this->getUserByToken($_COOKIE['accesstoken']);
        }

        if (!$this->user && !empty($this->request->param('Action'))) {
            return $this->httpError(404);
        }
    }

    public function doApply()
    {
        if (!$this->request->isAjax()) {
            return $this->httpError(404, 'Request type not allowed!');
        }

        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        if ($this->request->isPost()) {
            
            $errors = $this->validate();

            if (!empty($errors)) {
                $listedErrors = implode('</li><li>', $errors);
                return $this->httpError(400, "<ul><li>{$listedErrors}</li></ul>");
            }

            if ($this->checkExisting()) {
                return $this->httpError(400, 'The member already exists!');
            }

            return $this->createMember();
        }

        return $this->httpError(400, 'Method not allowed!');
    }

    private function createMember()
    {
        $customer = Customer::create()->update([
            'FirstName' => $this->firstName,
            'LastName' => $this->lastName,
            'Email' => $this->email,
            'Password' => Customer::hashPassword($this->password),
        ]);

        $customer->write();
        $customer->SendVerificationEmail();
        $customer->syncToMailchimp();

        return [
            'message' => 'Your account has been created. Please check your email for the verification link.',
            'redirect' => '/member/me',
        ];
    }

    private function validate()
    {
        $errors = [];

        return $errors;
    }

    public function getTitle()
    {
        if ($this->job) {
            return $this->job->Title;
        }

        return '人人有砖搬';
    }

    public function index()
    {
        $action = $this->request->param('Action');
        $id = $this->request->param('ID');
        $id = (int) str_replace('referral-opportunity-', '', $id);

        if ($action && !$id) {
            return $this->redirect('/referral-opportunities');
        }

        if (!empty($action) && !empty($id)) {
            $this->job = ReferralOpportunity::get()->filter(['ValidUntil:GreaterThanOrEqual' => time()])->byID($id);
            if (empty($this->job)) {
                return $this->httpError(404);
            }
        }

        return $this->renderWith(['Page']);
    }

    public function getData()
    {
        $action = $this->request->param('Action');
        $id = $this->request->param('ID');
        $id = (int) str_replace('referral-opportunity-', '', $id);

        if (!empty($action) && $this->hasMethod($action)) {
            if (!empty($id)) {
                $this->job = ReferralOpportunity::get()->filter(['ValidUntil:GreaterThanOrEqual' => time()])->byID($id);
            }

            return $this->$action();
        }

        $content = !$this->user
            ? ('<p>&nbsp;</p><p>Calling all CITANZ members! Want to <strong>power up your network, level up your peers\' careers, and earn extra $$$ from your companies\' finder\'s fee policy?</strong> Our brand-new referral platform is about to launch, connecting you with sizzling IT jobs posted by fellow CITANZ members.</p><p>Think <strong>dream gigs, community cred, and skyrocketing referral rep</strong>, all while building a stronger IT ecosystem!</p><p>CITANZ\'s referral programme is now in beta✨. Please <a href="/member/me">sign in</a> to view the opportunities.</p>')
            : null
        ;

        $list = $this->getJobList();

        if ($this->user) {
            if (!$this->user->CanViewListing) {
                $content = '<h2 class="mb-4">You are not a paid member or alumni.</h2><p>The referral opporunities are member-only, thanks for understanding!</p>';
            } elseif (empty($list['list'])) {
                if ($this->user->CanCreateReferralOpportunities) {
                    $content = '<h2 class="mb-4">We don\'t have any referral opporunities listed just yet.</h2><p>Want to <a href="/member/referralopportunities">post one</a>?</p>';
                } elseif ($this->user->CanViewListing) {
                    $content = '<h2 class="mb-4">We don\'t have any referral opporunities listed just yet.</h2><p>Please check back later 👋</p>';
                }
            }
        }

        return array_merge(
            Page::create()->Data,
            [
                'title' => 'Referral Opportunities',
                'excerpt' => 'Unleash your network, empower your peers! Discover exclusive CITANZ referral opportunities and ignite their career journey!',
                'hero' => SiteConfig::current_site_config()->ReferralHero()->exists()
                    ? [ SiteConfig::current_site_config()->ReferralHero()->getData('ScaleWidth', 980) ]
                    : null
                ,
                'content' => $content,
                'pagetype' => 'ReferralsPage',
            ],
            $list,
        );
    }

    private function getJobList()
    {
        if (!$this->user || !$this->user->isValidMembership()) {
            return [
                'list' => null,
                'pages' => 0,
            ];
        }

        $page = (int) $this->request->getVar('page') - 1;
        $page = $page <= 0 ? 0 : $page;
        $list = ReferralOpportunity::get()
            ->filter([
                'ValidUntil:GreaterThanOrEqual' => time()
            ])
        ;

        $count = $list->count();
        $list = $list->limit(static::PAGE_SIZE, static::PAGE_SIZE * $page);

        return [
            'list' => array_map(fn($item) => $item->TileDataPublic, $list->toArray()),
            'pages' => ceil($count / static::PAGE_SIZE),
        ];
    }

    private function view()
    {
        if (!$this->user || !$this->job || !$this->user->CanViewListing) {
            return $this->httpError(404);
        }

        return array_merge(
            $this->job->Data,
            Page::create()->Data,
            [
                'title' => $this->job->Title,
                'content' => $this->job->JobDescription,
                'pagetype' => 'ReferralPage',
                'id' => $this->job->ID,
                'hasApplied' => $this->user->JobApplications()->filter(['JobID' => $this->job->ID])->exists(),
            ],
        );
    }
}
