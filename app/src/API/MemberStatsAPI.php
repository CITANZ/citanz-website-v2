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
use SilverStripe\ORM\DB;

class MemberStatsAPI extends RestfulController
{
    use OAuthTrait;

    private $user = null;
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
            return $this->httpError(401, 'Unauthorised');
        }

        if ($action = $request->param('action')) {
            return $this->$action($request);
        }

        return $this->httpError(404);
    }

    private function ExpiredMembers()
    {

    }

    public static function getExpiredMembers()
    {
        return DB::query("SELECT c.* FROM Cita_eCommerce_Customer AS c INNER JOIN Cita_eCommerce_Order AS o ON c.ID = o.CustomerID WHERE o.Status = 'Completed' AND c.Expiry < CURDATE() GROUP BY c.ID");
    }

    public static function getHistoricalPaidMembers()
    {
        return DB::query("SELECT c.* FROM Cita_eCommerce_Customer AS c INNER JOIN Cita_eCommerce_Order AS o ON c.ID = o.CustomerID WHERE o.Status = 'Completed' GROUP BY c.ID");
    }

    public static function getRecentlyRenewedMembers()
    {
        $IDs = DB::query("SELECT o.CustomerID FROM Omnipay_Payment AS p INNER JOIN Cita_eCommerce_Order AS o ON o.ID = p.OrderID WHERE p.Status = 'Captured' AND p.LastEdited > MAKEDATE(year(now()), 1) GROUP BY o.CustomerID")->column('CustomerID');

        $customers = Customer::get()->filter(['ID' => $IDs]);

        return array_filter(
            $customers->toArray(),
            function ($customer) {
                return $customer->Orders()->count() > 1;
            }
        );
    }

    public static function getNewMembers()
    {
        $IDs = DB::query("SELECT o.CustomerID FROM Omnipay_Payment AS p INNER JOIN Cita_eCommerce_Order AS o ON o.ID = p.OrderID WHERE p.Status = 'Captured' AND p.LastEdited > MAKEDATE(year(now()), 1) GROUP BY o.CustomerID")->column('CustomerID');

        $customers = Customer::get()->filter(['ID' => $IDs]);

        return array_filter(
            $customers->toArray(),
            function ($customer) {
                return $customer->Orders()->count() == 1;
            }
        );
    }
}
