<?php

namespace App\Web\Extension;

use Cita\eCommerce\Model\SubscriptionOrder;
use SilverStripe\ORM\DataExtension;
use Cita\eCommerce\Model\CustomerGroup;

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
                    'BillingApartment' => $primaryAddress->Address,
                    'BillingAddress' => $primaryAddress->Apartment,
                    'BillingSuburb' => $primaryAddress->Suburb,
                    'BillingTown' => $primaryAddress->City,
                    'BillingRegion' => $primaryAddress->Region,
                    'BillingCountry' => $primaryAddress->Country,
                    'BillingPostcode' => $primaryAddress->Postcode,
                    'BillingPhone' => $primaryAddress->Phone,
                ]);
            }
        }
    }

    public function doPaymentSuccessAction(&$order)
    {
        if ($order->ClassName === SubscriptionOrder::class) {
            if ($variant = $order->Variants()->first()) {
                if ($order->Customer()->exists()) {
                    $newExpiry = $order->Customer()->extendExpiry($variant->Duration);
                }
            }

            $order->update([
              'Status' => 'Completed',
              'Comment' => !empty($newExpiry) ? 'Membership extended to ' . date('d/m/Y', is_int($newExpiry) ? $newExpiry : strtotime($newExpiry)) : null,
            ])->write();

            foreach ($order->Variants() as $variant) {
                $order->Variants()->add($variant->ID, ['Delivered' => true]);
            }

            if ($order->Customer()->exists()) {
                if ($paidMemberGroup = CustomerGroup::get()->filter(['Title' => 'Paid members'])->first()) {
                    $paidMemberGroup->Customers()->add($order->CustomerID);
                }
            }
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
