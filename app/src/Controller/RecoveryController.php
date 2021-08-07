<?php

namespace App\Web\Layout;
use PageController;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\Director;
use SilverStripe\Security\Member;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Security\IdentityStore;

/**
 * Description
 *
 * @package silverstripe
 * @subpackage mysite
 */
class RecoveryController extends PageController
{
    public function index(HTTPRequest $request)
    {
        if (($member_id = $request->getVar('id')) && ($token = $request->getVar('token'))) {
            if ($member = Member::get()->byID($member_id)) {
                if ($member->ValidationKey == $token) {
                    $member->ValidationKey  =   null;
                    $member->write();

                    Injector::inst()->get(IdentityStore::class)->logIn($member, true);

                    return $this->redirect('/member/password');
                }

                return $this->httpError(401, 'Token has expired or invalid');
            }

            return $this->httpError(404, 'no such member');
        }

        return $this->renderWith([$this->ClassName, 'Page']);
    }

    public function Link($action = null)
    {
        return 'token-login';
    }

    public function AbsoluteLink($action = null)
    {
        return Director::absoluteBaseURL() . $this->Link();
    }
}
