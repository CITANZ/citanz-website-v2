<?php

namespace App\Web\API;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use ZxcvbnPhp\Zxcvbn;
use Cita\eCommerce\Model\Customer;
use PMW\Util\PasswordStrength;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;
use Cita\eCommerce\Model\MemberVerificationCode;
use Cita\eCommerce\eCommerce;
use Cita\eCommerce\Model\SubscriptionOrder;
use SilverStripe\Core\Environment;
use Cita\eCommerce\Service\PaymentService;
use Cita\eCommerce\Model\Subscription;
use App\Web\Model\StudentDiscountApplication as Application;
use SilverStripe\AssetAdmin\Controller\AssetAdmin;
use SilverStripe\Assets\Folder;
use SilverStripe\Assets\Image;

class StudentDiscountApplication extends RestfulController
{
    use OAuthTrait;

    private $user = null;
    private $application = null;

    /**
     * Defines methods that can be called directly.
     *
     * @var array
     */
    private static $allowed_actions = [
        'get' => true,
        'post' => true,
    ];

    public function post($request)
    {
        $action = $request->param('action');
        $id = $request->params('id');

        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        $this->application = $this->user->StudentDiscountApplications()->filter(['Approved' => false, 'Rejected' => false])->first();

        if (!$this->application) {
            $this->application = Application::create()->update(['CustomerID' => $this->user->ID]);
            $this->application->write();
        }

        if ($action && $this->hasMethod($action)) {
            return $this->$action($request);
        }

        return [
            'user' => $this->user,
            'application' => $this->application,
        ];
    }

    public function attach(&$request)
    {
        $rawPhoto = $request->postVar('studentIDPhoto');

        if (empty($rawPhoto)) {
            return $this->httpError(400, 'You uploaded nothing!');
        }

        if ($rawPhoto['error']) {
            return $this->httpError(400, $rawPhoto['error']);
        }

        $folder = Folder::find_or_make('customers/' . $this->user->GUID);

        $titles = explode('.', $rawPhoto['name']);
        array_pop($titles);

        $image = Image::create();
        $image->setFromLocalFile($rawPhoto['tmp_name'], $this->generateRandomFileName($rawPhoto['name']));
        $image->ParentID = $folder->ID;
        $image->Title = implode('.', $titles);
        $image->writeToStage('Stage');

        AssetAdmin::create()->generateThumbnails($image);

        if ($this->application->StudentIDFile()->exists()) {
            $this->application->StudentIDFile()->deleteFromStage('Stage');
            $this->application->StudentIDFile()->deleteFromStage('Live');
        }

        $this->application->update([
            'StudentIDFileID' => $image->ID,
        ])->write();

        return [
            'user' => $this->user,
            'application' => $this->application,
        ];
    }

    private function generateRandomFileName($filename)
    {
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        return bin2hex(random_bytes(20)) . '.' . $ext;
    }
}
