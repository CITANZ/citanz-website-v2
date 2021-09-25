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

class Member extends RestfulController
{
    use OAuthTrait;

    private const ANONYMOUS_METHODS = [
      'passwordRecovery',
      'setPassword',
    ];

    private $user = null;
    /**
     * Defines methods that can be called directly.
     *
     * @var array
     */
    private static $allowed_actions = [
        'get' => true,
        'post' => true,
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

    public function post($request)
    {
        $action = $request->param('action');

        if (in_array($action, static::ANONYMOUS_METHODS)) {
            return $this->$action($request);
        }

        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        if ($action && $this->hasMethod($action)) {
            return $this->$action($request);
        }

        return $this->httpError(401, 'Unauthorised');
    }

    public function getMembershipData()
    {
        $subscriptions = Subscription::get()->MiniData;
        $discount = null;

        if ($group = $this->user->Groups()->first()) {
            if ($group->Discount()->exists()) {
                $discount = $group->Discount();
            }
        }

        if ($discount) {
            foreach ($subscriptions as &$subscription) {
                $lowest = null;
                $highest = 0;
                foreach ($subscription['variants'] as &$variant) {
                    $amount = $variant['price'];
                    $variant['originalPrice'] = '$' . number_format($amount, 2);

                    if ($discount->DiscountBy == 'ByPercentage') {
                        $amount = $amount * (1 - $discount->DiscountRate * 0.01);
                    } else {
                        $amount = $amount - $discount->DiscountRate;
                        $amount = $amount < 0 ? 0 : $amount;
                    }

                    if ($this->user->isRealStudent()) {
                        $variant['variant_title'] .= ' for Student';
                    } else {
                        $variant['variant_title'] = 'Renew membership';
                    }

                    $variant['price'] = $amount;
                    $variant['price_label'] = '$' . number_format($amount, 2);

                    if (is_null($lowest)) {
                        $lowest = $amount;
                    } else {
                        $lowest = $amount < $lowest ? $amount : $lowest;
                    }

                    $highest = $amount > $highest ? $amount : $highest;
                }

                $subscription['price_label'] = '$' . ($lowest == $highest ? number_format($lowest, 2) : (number_format($lowest, 2) . ' - ' . number_format($highest, 2)));
            }
        }

        $data = [
            'user' => $this->user,
            'subscriptions' => $subscriptions,
        ];

        if ($discount) {
          $discountValidUntil = date('d/m/Y', strtotime($this->user->Expiry . ' +30 days'));
          $data = array_merge(
              $data,
              [
                  'discountDesc' => "<small><em>* The discount of {$discount->Description} is valid until <u>{$discountValidUntil}</u></em></small>"
              ]
          );
        }

        return $data;
    }

    public function prepareMembership(&$request)
    {
        if ($address = $request->postVar('address')) {
            $addressData = json_decode($address, true);
            $this->user->updateAddress($addressData);
        }

        if (!$this->user->Addresses()->exists() || empty($this->user->Addresses()->first()->Address)) {
            return $this->httpError(400, 'Cannot detect address');
        }

        $vid = Convert::raw2sql($this->request->postVar('id'));

        if (empty($vid)) {
            return $this->httpError(400, 'Missing subscription vid');
        }

        $cart = eCommerce::get_subscription_cart(null, $this->user);

        if (!$cart) {
            $cart = SubscriptionOrder::create()->update([
                'CustomerID' => $this->user->ID,
            ]);

            $cart->write();
            $session = $this->request->getSession();
            $session->set('subscription_cart_id', $cart->ID);
        }

        return array_merge(
            $cart->AddToCart($vid),
            [
                'stripe_key' => json_decode(Environment::getEnv('Stripe'))->public,
                'user' => $this->user,
            ]
        );
    }

    public function getOrderDetails()
    {
        $order = $this->user->Orders()->byID($this->request->getVar('id'));

        if (!$order) {
            return $this->httpError(404, 'Order not found');
        }

        return $order->VueUIData;
    }

    public function payMembership(&$request)
    {
        $order = eCommerce::get_subscription_cart(null, $this->user);
        $result = PaymentService::initiate('Stripe', $order, $request->postVar('token'));

        if (!$result['success']) {
            $this->httpError(402, $result['message']);
        }

        return $result;
    }

    public function setProfile(&$request)
    {
        $data = $request->postVars();
        $addressData = json_decode($data['Address'], true);
        unset($data['Address']);

        $this->user->update($data)->write();
        $this->user->updateAddress($addressData);

        return [
            'message' => 'Your profile has been updated!',
            'user' => $this->user,
            'profile' => $this->user->FullProfile,
        ];
    }

    public function getFullProfile(&$request)
    {
        return [
            'full' => $this->user->FullProfile,
            'basic' => $this->user,
        ];
    }

    public function getSecuritySectionData(&$request)
    {
        $password = $this->user->Password;
        return [
            'qrString' => $this->user->MyQRCode,
        ];
    }

    public function getPayments(&$request)
    {
        $page = $request->getVar('page');
        $page = empty($page) || $page < 0 ? 0 : (((int) $page) - 1);
        $pageSize = 10;

        $list = $this->user->Orders()->filter([
            'Status' => ['PaymentReceived', 'Shipped', 'Cancelled', 'Refunded', 'CardCreated', 'Completed', 'Free Order']
        ]);

        $count = $list->count();
        $orders = $list->limit($pageSize, $page * $pageSize)->toArray();

        return [
            'list' => array_map(function($order) {
                $paymentData = (object) $order->SuccessPayment->Data;
                return [
                    'id' => $order->ID,
                    'ref' => $order->CustomerReference,
                    'date' => $paymentData->created,
                    'status' => $order->Status,
                    'amount' => '$' . number_format($paymentData->amount, 2),
                ];
            }, $orders),
            'pageSize' => $pageSize,
            'pages' => ceil($count / $pageSize),
        ];
    }

    public function updatePassword(&$request)
    {
        $password = $request->postVar('password');

        if ((new Zxcvbn())->passwordStrength($password)['score'] >= 2) {
            $this->user->update([
                'Password' => Customer::hashPassword($password),
            ])->write();

            $password = $this->user->Password;

            return [
                'message' => 'Your password has been updated!',
                'qrString' => $this->user->MyQRCode,
            ];
        }

        return $this->httpError(400, 'Password is not strong enough!');
    }

    public function setPassword(&$request)
    {
        $token = $request->getSession()->get('passwordReoveryToken');

        if ($token) {
            if ($code = MemberVerificationCode::get()->filter(['Code' => $token, 'Invalid' => false])->first()) {
                if ($password = $request->requestVar('password')) {
                    if ((new Zxcvbn())->passwordStrength($password)['score'] >= 2) {
                        $code->Customer()->update([
                            'Password' => Customer::hashPassword($password),
                        ])->write();

                        $request->getSession()->clear('passwordReoveryToken');

                        return [
                            'message' => 'Your password has been updated! Please sign in again.',
                            'redirect' => '/member/me',
                        ];
                    }

                    return $this->httpError(400, 'Password is not strong enough!');
                }
            }
        }

        return $this->httpError(403, 'Invalid token. Please re-apply password recovery!');
    }

    public function resendActiviationCode(&$request)
    {
        if (!$request->isPost()) {
            return $this->httpError(400, 'Method not allowed');
        }

        $session = $this->request->getSession();
        $lastSent = $session->get('lastVerificationSent') ?? time();
        $elapsed = time() - $lastSent;

        if ($elapsed < 60) {
            return $this->httpError(400, 'Request too frequently! (' . (60 - $elapsed) . ' second(s) to go)');
        }

        $this->user->SendVerificationEmail();

        $session->set('lastVerificationSent', time());

        return [
            'message' => '<p>Your activation code has been sent to your email address. Make sure you also check your spambox.</p>',
        ];
    }

    public function passwordRecovery(&$request)
    {
        $email = $request->postVar('email');

        if (!$email) {
            return $this->httpError(400, 'Missing email');
        }

        $member = Customer::get()->filter(['Email' => $email])->first();

        if (!$member) {
            return $this->httpError(404, 'No such account!');
        }

        $member->RequestPasswordReset();

        return [
            'message' => '<p>The password recovery link has been sent to your email address. Make sure you also check your spambox.</p>',
        ];
    }

    public function doActivate(&$request)
    {
        $code = $request->postVar('activationCode');

        if (empty($code)) {
            return $this->httpError(400, 'Missing activation code!');
        }

        $found = $this->user->VerificationCodes()->filter([
            'Code' => $code,
            'Type' => 'activation',
            'Invalid' => false,
        ])->first();

        if ($found) {
            $found->Invalid = true;
            $found->write();
            $this->user->Verified = true;
            $this->user->write();

            return [
                'message' => 'Thanks! Your account has been activated!',
                'user' => $this->user->jsonSerialize(),
            ];
        }

        return $this->httpError(400, 'Invalid activation code!');
    }
}
