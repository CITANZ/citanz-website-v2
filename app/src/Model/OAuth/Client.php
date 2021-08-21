<?php

namespace App\Web\Model\OAuth;

use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\ReadonlyField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\TextareaField;
use SilverStripe\Forms\CheckboxField;
use Ramsey\Uuid\Uuid;
use Cita\eCommerce\Model\Customer;

class Client extends DataObject
{
    private static $table_name = 'OAuth_Client';

    private static $db = [
        'GUID' => 'Varchar(40)',
        'Name' => 'Varchar(255)',
        'PersonalAccessClient' => 'Boolean',
        'PasswordClient' => 'Boolean',
        'Redirect' => 'Text',
        'Secret' => 'Varchar(120)',
        'Revoked' => 'Boolean',
        'SecretHashed' => 'Boolean',
    ];

    private static $has_one = [
        'Customer' => Customer::class,
    ];

    private static $indexes = [
        'GUID' => [
            'type' => 'unique',
        ],
    ];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'GUID',
            'Revoked',
            'Secret',
            'PersonalAccessClient',
            'PasswordClient',
            'CustomerID',
            'Redirect',
            'SecretHashed',
        ]);

        $secretDescription  = 'The client secret that will be sent with any requests.';
        $secretDescription .= ' The secret will be hashed once saved, so keep note of it.';

        if ($this->isInDB()) {
            $fields->addFieldToTab(
                'Root.Main',
                ReadonlyField::create('GUID', 'GUID')
                    ->setDescription('This is the client id that will be sent with any requests.'),
                'Name'
            );

            $fields->addFieldToTab(
                'Root.Main',
                TextareaField::create('Redirect', 'Redirect')
                    ->setDescription('Url to redirect to once authentication has been made.')
            );

            $fields->addFieldToTab(
                'Root.Main',
                CheckboxField::create('Revoked', 'Revoked')
                    ->setDescription('Revoke access to this client.')
            );
        } else {
            $fields->addFieldsToTab(
                'Root.Main',
                [
                    TextField::create('Secret', 'Secret')
                        ->setDescription($secretDescription),
                    TextareaField::create('Redirect', 'Redirect')
                        ->setDescription('Url to redirect to once authentication has been made.'),
                ]
            );
        }

        return $fields;
    }

    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        if (!$this->SecretHashed) {
            $secret = $this->Secret;

            $this->Secret = password_hash($secret, PASSWORD_DEFAULT);
            $this->SecretHashed = true;
        }

        if (empty($this->GUID)) {
            $uuid = Uuid::uuid4();
            $this->GUID = $uuid->toString();
        }
    }
}
