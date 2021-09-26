<?php

namespace App\Web\Model;

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
    ];

    private static $searchable_fields = [
        'Customer.FirstName',
    ];

    private static $default_sort = ['Created' => 'DESC'];

    public function getCMSFields()
    {
        $fields = parent::getCMSFields();
        $fields->removeByName([
            'CustomerID',
            'Approved',
            'Rejected',
            'EmailSent',
            'StudentIDFile',
        ]);

        $imageSrc = $this->StudentIDFile()->exists() ? $this->StudentIDFile()->URL : null;

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
              LiteralField::create('Image', $imageSrc ? ('<p>Student ID:</p><p><img src="' . $imageSrc . '" /></p>') : 'NO IMAGE')
          ]
        );
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

    public function canEdit($member = null)
    {
        if (!$member) {
            return false;
        }

        return $member->isDefaultAdmin();
    }

    public function canDelete($member = null)
    {
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
        $email = Email::create('noreply@cita.org.nz', $customer->Email, '[CITANZ] New student account application received');
        $body = <<<MSG
  <p>Hi {$customer->FirstName}</p>

  <p>We're writing to let you know that your CITANZ student discount application has been <strong>{$this->Decision}</strong>.</p>

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
