<?php

namespace App\Web\Extension;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Extension;
use SilverStripe\Omnipay\Model\Payment;
use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldDataColumns;
use SilverStripe\Forms\GridField\GridFieldFilterHeader;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\GridField\GridFieldExportButton;

/**
 * @file SiteConfigExtension
 *
 * Extension to provide Open Graph tags to site config.
 */
class CustomerAdminExtension extends Extension
{
    /**
     * Managed data objects for CMS
     * @var array
     */
    private static $managed_models = [
        Payment::class,
    ];

    public function updateList(&$list)
    {
        if ($this->owner->modelClass == Payment::class) {
            $list = $list->filter(['Status' => 'Captured']);

            if (!empty($this->owner->getRequest()->postVar('filter'))) {
                $params = $this->owner->getRequest()->postVar('filter');

                if (!empty($params['SilverStripe-Omnipay-Model-Payment'])) {
                    $params = (object) $params['SilverStripe-Omnipay-Model-Payment'];
                    $query = [];

                    if (!empty($params->FirstName)) {
                        $query['Order.ShippingFirstname:nocase'] = $params->FirstName;
                    }

                    if (!empty($params->LastName)) {
                        $query['Order.ShippingSurname:nocase'] = $params->LastName;
                    }

                    if (!empty($params->Email)) {
                        $query['Order.Email:nocase'] = $params->Email;
                    }

                    $list = $list->filter($query);
                }
            }
        }
    }

    public function updateGridFieldConfig(&$config)
    {
        if ($this->owner->modelClass == Payment::class) {
            $export = $config->getComponentByType(GridFieldExportButton::class);
            $export->setExportColumns([
                'Order.CustomerReference' => 'Order#',
                'Order.Customer.CitaID' => 'Member ID',
                'Order.Customer.Title' => 'Member',
                'Order.CommentText' => 'Comment',
                'Money' => 'Amount',
                'Status' => 'Status',
                'Order.Paidat' => 'Paid at',
            ]);
        }
    }

    public function updateEditForm(&$form)
    {
        $gridField = $form->Fields()->dataFieldByName($this->sanitiseClassName($this->owner->modelClass));

        if (empty($gridField) || !($gridField instanceof GridField) || $this->owner->modelClass != Payment::class) {
            return;
        }

        $config = $gridField->getConfig();
        $dataColumns = $config->getComponentByType(GridFieldDataColumns::class);
        $dataColumns->setDisplayFields([
            'Order.CustomerReference' => 'Order#',
            'Order.Customer.CitaID' => 'Member ID',
            'Order.Customer.Title' => 'Member',
            'Order.CommentText' => 'Comment',
            'Money' => 'Amount',
            'Status' => 'Status',
            'Order.Paidat' => 'Paid at',
        ])->setFieldCasting([
            'Money' => 'Currency->Nice',
            'CartItemList' => 'HTMLText->RAW',
            'Order.Paidat' => 'Datetime->Nice',
        ]);

        if ($this->owner->modelClass == Payment::class) {
            $filter = $config->getComponentByType(GridFieldFilterHeader::class);
            $context = $filter->getSearchContext($gridField);
            $context->getFields()->removeByName([
                'Gateway',
                'Status',
                'GatewayTitle',
                'PaymentStatus'
            ]);

            $context->getFields()->push(TextField::create(
                'FirstName',
                'First name'
            ));

            $context->getFields()->push(TextField::create(
                'LastName',
                'Last name'
            ));

            $context->getFields()->push(TextField::create(
                'Email',
                'Email'
            ));
        }
    }

    /**
     * Sanitise a model class' name for inclusion in a link
     *
     * @param string $class
     * @return string
     */
    protected function sanitiseClassName($class)
    {
        return str_replace('\\', '-', $class);
    }
}
