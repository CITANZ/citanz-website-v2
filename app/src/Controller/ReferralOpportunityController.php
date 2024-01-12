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
        return '人人有砖搬';
    }

    public function index()
    {
        return $this->renderWith(['Page']);
    }

    public function getData()
    {
        $content = SiteConfig::current_site_config()->ReferralComingSoonImage()->exists()
            && (!$this->user || $this->user->CitaID != 'CITANZ-0003')
            ? ('<p>&nbsp;</p><p>Calling all CITANZ members! Want to <strong>power up your network and level up your peers\' careers?</strong> Our brand-new referral platform is about to launch, connecting you with sizzling IT jobs posted by fellow CITANZ members.</p><p>Think <strong>dream gigs, community cred, and skyrocketing referral rep</strong>, all while building a stronger IT ecosystem.  Stay tuned – the future of collaboration is about to go online! ✨</p><p><img src="' . SiteConfig::current_site_config()->ReferralComingSoonImage()->URL . '" alt="coming soon image" width="600" /></p>')
            : null
        ;

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
            $this->getJobList(),
        );
    }

    private function getJobList()
    {
        if (!$this->user) {
            return [
                'list' => null,
                'pages' => 0,
            ];
        }

        $page = (int) $this->request->getVar('page') -1;
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
}
