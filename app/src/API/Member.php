<?php

namespace App\Web\API;

use SilverStripe\Core\Convert;

use Cita\eCommerce\Model\Customer;
use PMW\Util\PasswordStrength;
use SilverStripe\Control\Controller;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;

class Member extends RestfulController
{
  use OAuthTrait;
  /**
   * Defines methods that can be called directly.
   *
   * @var array
   */
  private static $allowed_actions = [
      'get' => true,
  ];

  public function get($request)
  {
    $this->user = $this->authenticate();

    if (!$this->user || $this->user instanceof HTTPResponse) {
      return $this->httpError('Unauthorised', 401);
    }

    if ($action = $request->param('action')) {
      return $this->$action($request);
    }

    return $this->user;
  }
}
