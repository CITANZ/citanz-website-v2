<?php

namespace App\Web\Page;

use SilverStripe\Dev\Debug;
use Page;
use PageController;
use Cita\eCommerce\Model\Customer;
use Cita\eCommerce\Model\MemberVerificationCode;

/**
 * Description.
 */
class MemberCentreController extends PageController
{
    protected $Title = 'Member centre';

    public function index()
    {
        $action = $this->request->param('action');
        if ($action == 'passwordRecovery') {
            return $this->handlePasswordRecovery();
        } elseif ($action == 'reset-password' && !$this->request->getSession()->get('passwordReoveryToken')) {
            return $this->redirect('/member/me');
        }

        return $this->renderWith(['Page']);
    }

    public function handlePasswordRecovery()
    {
        $token = $this->request->requestVar('recovery_token');

        if ($token) {
            if (MemberVerificationCode::get()->filter(['Code' => $token, 'Invalid' => false])->exists()) {
                $this->request->getSession()->set('passwordReoveryToken', $token);
                return $this->redirect('/member/reset-password');
            }
        }

        return $this->httpError(404);
    }

    protected function init()
    {
        parent::init();
        
        $route = $this->request->param('action');

        if ($route == 'referralopportunities') {
            $this->Title = $this->ReferralOpportunitiesData['title'];
        } elseif ($route == 'security') {
            $this->Title = $this->SecurityData['title'];
        } elseif ($route == 'membership') {
            $this->Title = $this->MembershipData['title'];
        } elseif ($route == 'payments') {
            $this->Title = $this->PaymentsData['title'];
        }
    }

    public function getData()
    {
        $data = Page::create()->Data;

        $method = "getDefaultData";

        if ($action = $this->request->param('action')) {
            $action = ucwords($action === 'me' ? 'Default' : str_replace('-', '', $action));
            $method = "get{$action}Data";

            if (!$this->hasMethod($method)) {
                return $this->httpError(404);
            }
        }

        return array_merge(
            $data,
            $this->$method(),
            [
                'pagetype' => 'MemberCentre',
                'memberMenu' => $this->MemberMenu,
                'recoveryMode' => $this->request->getSession()->get('passwordReoveryToken') ? true : false,
            ]
        );
    }

    public function getMembershipData()
    {
        $data = $this->DefaultData;
        $data['title'] = 'Member centre - CITANZ Membership';
        return $data;
    }

    public function getResetpasswordData()
    {
        return $this->DefaultData;
    }

    public function getMemberMenu()
    {
        if ($pendingRecovery = $this->request->getSession()->get('passwordReoveryToken')) {
            return [
                [
                    'title' => 'Reset password',
                    'url' => '/member/reset-password',
                    'icon' => 'mdi-lock',
                ],
            ];
        }

        return [
            [
                'title' => 'Profile',
                'url' => '/member/me',
                'icon' => 'mdi-account-circle',
            ],
            [
                'title' => 'Referral Opportunities',
                'url' => '/member/referralopportunities',
                'icon' => 'mdi-playlist-star',
            ],
            [
                'title' => 'Security',
                'url' => '/member/security',
                'icon' => 'mdi-lock',
            ],
            [
                'title' => 'Membership',
                'url' => '/member/membership',
                'icon' => 'mdi-account-heart',
            ],
            [
                'title' => 'Payments',
                'url' => '/member/payments',
                'icon' => 'mdi-currency-usd',
            ],
        ];
    }

    public function getPaymentsData()
    {
      return [
          'title' => 'Member centre - Payment History',
      ];
    }

    public function getSecurityData()
    {
      return [
          'title' => 'Member centre - Security',
      ];
    }

    public function getReferralOpportunitiesData()
    {
        return [
            'title' => 'Member centre - Referral Opportunities',
        ];
    }

    public function getViewData()
    {
        $id = $this->request->param('id');

        if (!$id) {
            return $this->httpError(404);
        }

        $member = Customer::get()->filter(['GUID' => $id])->first();

        if (!$member) {
            return $this->httpError(404);
        }

        return [
            'title' => 'View',
        ];
    }

    public function getDefaultData()
    {
        $lastSent = $this->request->getSession()->get('lastVerificationSent') ?? time();
        $elapsed = time() - $lastSent;

        return [
            'title' => 'Member centre',
            'lastSent' => 60 - $elapsed,
        ];
    }
}
