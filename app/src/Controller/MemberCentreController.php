<?php

namespace App\Web\Page;

use SilverStripe\Dev\Debug;
use Page;
use PageController;
use Cita\eCommerce\Model\Customer;

/**
 * Description.
 */
class MemberCentreController extends PageController
{
    protected $Title = 'Member centre';

    public function getData()
    {
        $data = Page::create()->Data;

        $method = "getDefaultData";

        if ($action = $this->request->param('action')) {
            $action = ucwords($action === 'me' ? 'Default' : $action);
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
                'memberMenu' => [
                    [
                        'title' => 'Profile',
                        'url' => '/member/me',
                        'icon' => 'mdi-account-circle',
                    ],
                    [
                        'title' => 'Security',
                        'url' => '/member/security',
                        'icon' => 'mdi-lock',
                    ],
                    [
                        'title' => 'Payments',
                        'url' => '/member/payments',
                        'icon' => 'mdi-currency-usd',
                    ],
                ]
            ]
        );
    }

    public function getPaymentsData()
    {
      return [
          'title' => 'Member centre - Payment history',
      ];
    }

    public function getSecurityData()
    {
      return [
          'title' => 'Member centre - Security',
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
