<?php

namespace App\Web\Extension;

use Cita\eCommerce\Model\SubscriptionOrder;
use SilverStripe\ORM\DataExtension;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class OrderExtension extends DataExtension
{
    public function doPaymentSuccessAction(&$order)
    {
        if ($order->ClassName === SubscriptionOrder::class) {
            if ($variant = $order->Variants()->first()) {
                if ($order->Customer()->exists()) {
                    $order->Customer()->extendExpiry($variant->Duration);
                }
            }

            $order->update(['Status' => 'Completed'])->write();
        }
    }
}
