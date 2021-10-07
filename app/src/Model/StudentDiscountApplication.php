<?php

namespace App\Web\Model;

use SilverStripe\Security\Member;
use SilverStripe\Dev\Debug;
use SilverStripe\Control\Director;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Assets\Image;
use Cita\eCommerce\Model\Customer;
use SilverStripe\ORM\DataObject;
use SilverStripe\Forms\LiteralField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\Control\Email\Email;
use App\Web\Admin\StudentApplicationAdmin as MyModelAdmin;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\Control\Controller;
use Cita\eCommerce\Model\CustomerGroup;

class StudentDiscountApplication extends DataObject implements \JsonSerializable
{
    private static $table_name = 'StudentDiscountApplication';

    private static $db = [
        'Approved' => 'Boolean',
        'Rejected' => 'Boolean',
        'EmailSent' => 'Boolean',
        'ExpiryDate' => 'Date',
        'RejectReason' => 'Text',
    ];

    private static $has_one = [
        'Customer' => Customer::class,
        'StudentIDFile' => Image::class,
    ];

    private static $summary_fields = [
        'StudentIDFile.CMSThumbnail' => 'Student ID',
        'Title' => 'Customer',
        'Customer.Email' => 'Email',
        'Decision' => 'Approved?',
        'ExpiryDate' => 'Expiry date',
    ];

    private static $searchable_fields = [
        'Customer.FirstName',
    ];

    private static $cascade_deletes = [
        'StudentIDFile',
    ];

    private static $default_sort = ['Created' => 'DESC'];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $expiryField = $fields->fieldByName('Root.Main.ExpiryDate');
        $rejectField = $fields->fieldByName('Root.Main.RejectReason');

        if ($this->Approved) {
            $expiryField = $expiryField->performReadonlyTransformation();
        }

        if ($this->Rejected) {
            $rejectField = $rejectField->performReadonlyTransformation();
        }

        $fields->removeByName([
            'CustomerID',
            'Approved',
            'Rejected',
            'EmailSent',
            'StudentIDFile',
            'ExpiryDate',
            'RejectReason',
        ]);

        $imageSrc = $this->StudentIDFile()->exists() ? $this->StudentIDFile()->FillMax(1024, 1024)->URL : null;

        $fields->addFieldsToTab(
          'Root.Main',
          [
              HeaderField::create(
                  'Desicion',
                  'Application state: ' . $this->Decision
              ),
              HeaderField::create(
                  'CustomerTitle',
                  'Full name: ' . ($this->Customer()->exists() ? $this->Customer()->Title : 'UNKNOWN USER')
              ),
              HeaderField::create(
                  'CustomerEmail',
                  'Email: ' . ($this->Customer()->exists() ? $this->Customer()->Email : 'UNKNOWN EMAIL')
              ),
              $expiryField,
              $rejectField,
              LiteralField::create('Image', $imageSrc ? ('<p>Student ID:</p><p><img style="max-width: 100%; height: auto;" src="' . $imageSrc . '" /></p>') : 'NO IMAGE')
          ]
        );

        if ($this->Rejected) {
            $fields->removeByName([
                'ExpiryDate'
            ]);
        }

        if ($this->Approved) {
            $fields->removeByName([
                'RejectReason'
            ]);
        }

        return $fields;
    }

    public function getDecision()
    {
        if ($this->Approved) {
            return 'Approved';
        }

        if ($this->Rejected) {
            return 'Rejected';
        }

        return 'Pending approval';
    }

    public function canDelete($member = null)
    {
        $member = $member ?? Member::currentUser();

        if (!$member) {
            return false;
        }

        return $member->isDefaultAdmin();
    }

    public function getCMSEditLink()
    {
        $admin = Injector::inst()->get(MyModelAdmin::class);

        // Classname needs to be passeed as an action to ModelAdmin
        $classname = str_replace('\\', '-', $this->ClassName);

        return Controller::join_links(
            Director::absoluteBaseURL(),
            $admin->Link($classname),
            "EditForm",
            "field",
            $classname,
            "item",
            $this->ID,
            "edit"
        );
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();

        if (!$this->EmailSent) {
            $siteconfig = SiteConfig::current_site_config();

            if (!empty($siteconfig->StudentApplicationRecipient)) {
                $recipients = explode(',', $siteconfig->StudentApplicationRecipient);
                $Link = $this->CMSEditLink;

                foreach ($recipients as $recipient) {
                    $recipient = trim($recipient);
                    if (!empty($recipient)) {
                        $email = Email::create('noreply@cita.org.nz', $recipient, '[CITANZ] New student account application received');
                        $body = <<<MSG
<p>Hi admin</p>

<p>We've received a new student application.</p>

<p>To approve or reject, please click the link below, and make your decision on the CMS</p>

<p><a href="$Link" target="_blank">View application</a></p>

<p>Kind regards<br />
CITANZ</p>
MSG;
                        $email->setBody($body);
                        $email->send();
                    }
                }
            }

            $this->EmailSent = true;
            $this->write();
        }
    }

    public function getStudentGroup()
    {
        return CustomerGroup::get()->filter(['Title:nocase' => 'Student members'])->first();
    }

    public function approveApplication()
    {
        $this->Approved = true;
        $this->Rejected = false;
        $this->write();

        if (!$this->Customer()->exists()) {
            return;
        }

        if ($group = $this->StudentGroup) {
            $group->Customers()->add($this->Customer());
        }

        $this->notifyCustomer($this->Customer());
    }

    public function rejectApplication()
    {
        $this->Rejected = true;
        $this->Approved = false;
        $this->write();

        if (!$this->Customer()->exists()) {
            return;
        }

        if ($group = $this->StudentGroup) {
            $group->Customers()->remove($this->Customer());
        }

        $this->notifyCustomer($this->Customer());
    }

    private function notifyCustomer($customer)
    {
        $email = Email::create('noreply@cita.org.nz', $customer->Email, '[CITANZ] Student account application has been ' . strtolower($this->Decision));
        $reason = !empty($this->RejectReason) ? nl2br($this->RejectReason) : '';
        $extraInfo = $this->Rejected ? "Reason: <br /><blockquote style=\"padding: 0.5rem 0 0.5rem 1rem; margin: 1rem 0 2rem; font-style: italic; border-left: 4px solid #ccc;\">{$reason}</blockquote>" : '';

        $body = <<<MSG
  <p>Hi {$customer->FirstName}</p>

  <p>We're writing to let you know that your CITANZ student discount application has been <strong>{$this->Decision}</strong>.</p>

  {$extraInfo}

  <p>If you have any further questions, please contact us via <a href="mailto:info@cita.org.nz">info@cita.org.nz</a></p>

  <p>Kind regards<br />
  CITANZ</p>
  MSG;
        $email->setBody($body);
        $email->send();
    }

    public function Title()
    {
        return $this->getTitle();
    }

    public function getTitle()
    {
        return $this->Customer()->exists() ? trim($this->Customer()->FirstName . ' ' .  $this->Customer()->LastName) : 'UNKNOWN';
    }

    public function getData()
    {
        return [
            'id' => $this->ID,
        ];
    }

    public function jsonSerialize()
    {
        return $this->Data;
    }
}
