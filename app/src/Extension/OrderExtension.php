<?php

namespace App\Web\Extension;

use SilverStripe\Forms\FieldList;
use Cita\eCommerce\Model\SubscriptionOrder;
use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\CustomerGroup;
use Cita\eCommerce\Model\Customer;
use SilverStripe\Control\Email\Email;
use SilverStripe\Core\Environment;
use SilverStripe\SiteConfig\SiteConfig;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class OrderExtension extends DataExtension
{
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        if (!$this->owner->exists() && $this->owner->Customer()->exists()) {
            if ($primaryAddress = $this->owner->Customer()->Addresses()->first()) {
                $this->owner->update([
                    'ShippingFirstname' => $primaryAddress->FirstName,
                    'ShippingSurname' => $primaryAddress->Surname,
                    'ShippingAddress' => $primaryAddress->Address,
                    'ShippingOrganisation' => $primaryAddress->Company,
                    'ShippingApartment' => $primaryAddress->Apartment,
                    'ShippingSuburb' => $primaryAddress->Suburb,
                    'ShippingTown' => $primaryAddress->City,
                    'ShippingRegion' => $primaryAddress->Region,
                    'ShippingCountry' => $primaryAddress->Country,
                    'ShippingPostcode' => $primaryAddress->Postcode,
                    'ShippingPhone' => $primaryAddress->Phone,
                    'Email' => $this->owner->Customer()->Email,
                    'SameBilling' => true,
                    'BillingFirstname' => $primaryAddress->FirstName,
                    'BillingSurname' => $primaryAddress->Surname,
                    'BillingOrganisation' => $primaryAddress->Company,
                    'BillingApartment' => $primaryAddress->Apartment,
                    'BillingAddress' => $primaryAddress->Address,
                    'BillingSuburb' => $primaryAddress->Suburb,
                    'BillingTown' => $primaryAddress->City,
                    'BillingRegion' => $primaryAddress->Region,
                    'BillingCountry' => $primaryAddress->Country,
                    'BillingPostcode' => $primaryAddress->Postcode,
                    'BillingPhone' => $primaryAddress->Phone,
                ]);
            }
        }

        $this->owner->UpdateAmountWeight(true);
    }

    public function doPaymentSuccessAction(&$order)
    {
        if ($order->ClassName === SubscriptionOrder::class) {
            $member = $order->Customer();
            if ($variant = $order->Variants()->first()) {
                if ($member->exists()) {
                    $newExpiry = $member->extendExpiry($variant->Duration);
                    $citaId = $member->CitaID;
                    $isNew = false;

                    if (empty($citaId)) {
                        $isNew = true;
                        if ($latest = Customer::get()->sort('CitaID', 'DESC')->first()) {
                            $fullID = explode('-', $latest->CitaID);
                            if (count($fullID) > 1) {
                                $id = (int) $fullID[1];
                                $id++;
                                $citaId = 'CITANZ-' . str_pad($id, 4, "0", STR_PAD_LEFT);
                            }
                        }
                    }

                    $member->update([
                        'CitaID' => $citaId,
                        'Expiry30Reminded' => false,
                        'Expiry7Reminded' => false,
                        'Expiry0Reminded' => false,
                    ])->write();

                    if ($isNew) {
                        $member->sendMemberInductionKit();
                    }

                    if ($paidMemberGroup = CustomerGroup::get()->filter(['Title:nocase' => 'Paid members'])->first()) {
                        $paidMemberGroup->Customers()->add($order->CustomerID);
                    }

                    $member->updateMailchimpPaidTag();
                    $this->owner->notifyAdmin();
                }
            }

            $order->update([
              'Status' => 'Completed',
              'Comment' => !empty($newExpiry) ? 'Membership extended to ' . date('d/m/Y', is_int($newExpiry) ? $newExpiry : strtotime($newExpiry)) : null,
            ])->write();

            foreach ($order->Variants() as $variant) {
                $order->Variants()->add($variant->ID, ['Delivered' => true]);
            }
        }
    }

    public function notifyAdmin()
    {
        $member = $this->owner->Customer();

        if (!$member->exists()) {
            return;
        }

        $name = trim("{$member->FirstName} {$member->LastName}");
        $citaId = $member->CitaID;
        $recipients = explode(',', SiteConfig::current_site_config()->AccountAffairsRecipient);
        $amount = $this->owner->SuccessPayment ? $this->owner->SuccessPayment->Amount : 'UNKNOWN';
        $currency = $this->owner->SuccessPayment ? $this->owner->SuccessPayment->Currency : 'NZD';
        foreach ($recipients as $recipient) {
            $email = Email::create(
              'noreply@cita.org.nz',
              $recipient,
              "[CITANZ] $name ($citaId) has paid."
            );
            $link = Environment::getEnv('SS_BASE_URL') . "admin/customers/Cita-eCommerce-Model-Customer/EditForm/field/Cita-eCommerce-Model-Customer/item/{$member->ID}/edit";

            $body = <<<MSG
<p>Hi admin</p>

<p><a href="$link" target="_blank">$name ($citaId)</a> has paid {$amount} {$currency}.</p>

<p>Kind regards<br />
CITANZ</p>
MSG;
            $email->setBody($body);
            $email->send();
        }
    }

    public function CustomDirectCartItemList()
    {
        $items = array_map(function($v) {
            return "{$v->StoredTitle} x {$v->Quantity}";
        }, $this->owner->Variants()->toArray());

        return implode("\n", $items);
    }
}
