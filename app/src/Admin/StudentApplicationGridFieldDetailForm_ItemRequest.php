<?php

namespace App\Web\Admin;

use SilverStripe\Dev\Debug;
use SilverStripe\Forms\GridField\GridFieldDetailForm_ItemRequest;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\FormAction;
use SilverStripe\Control\Controller;
use SilverStripe\Core\Config\Config;
use SilverStripe\ORM\ValidationResult;

class StudentApplicationGridFieldDetailForm_ItemRequest extends GridFieldDetailForm_ItemRequest
{
    private static $allowed_actions = ['ItemEditForm'];

    public function ItemEditForm()
    {
        $form   =   parent::ItemEditForm();

        if ($this->record->exists()) {
            $formActions    =   FieldList::create();
            if (!$this->record->Rejected && !$this->record->Approved) {
                $formActions->push($this->create_button('doApprove', 'Approve', 'btn-primary'));
                if (empty($this->record->ExpiryDate)) {
                    $formActions->push($this->create_button('doReject', 'Reject', 'btn-outline-danger btn-hide-outline'));
                }
            }
        } else {
            $formActions    =   $form->Actions();
        }

        $form->setActions($formActions);

        return $form;
    }

    private function create_button($action, $label = null, $class = null)
    {
        $button =   FormAction::create($action);

        $button->setTitle($label ? $label : $action);
        if ($class) {
            $button->addExtraClass($class);
        }

        return $button;
    }

    public function doApprove($data, $form)
    {
        if (empty($data['ExpiryDate'])) {
            $form->sessionMessage('Please enter the expiry date!', 'bad', ValidationResult::CAST_HTML);
            return $this->edit(Controller::curr()->getRequest());
        }

        if (strtotime($data['ExpiryDate']) <= time()) {
            $form->sessionMessage('It\'s already expired!', 'bad', ValidationResult::CAST_HTML);
            return $this->edit(Controller::curr()->getRequest());
        }

        $form->sessionMessage('Application approved', 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->update(['ExpiryDate' => $data['ExpiryDate']])->write();
            $this->record->approveApplication();
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    public function doReject($data, $form)
    {
        if (empty($data['RejectReason'])) {
            $form->sessionMessage('Please provide the reason for rejecting this application!', 'bad', ValidationResult::CAST_HTML);
            return $this->edit(Controller::curr()->getRequest());
        }

        $form->sessionMessage('Application rejected', 'good', ValidationResult::CAST_HTML);

        if ($this->gridField->getList()->byId($this->record->ID)) {
            $this->record->update(['RejectReason' => $data['RejectReason']])->write();
            $this->record->rejectApplication();
            return $this->edit(Controller::curr()->getRequest());
        }

        return $this->goback($data);
    }

    private function goback(&$data)
    {
        $url    =   Controller::curr()->removeAction($data['BackURL']);
        Controller::curr()->getRequest()->addHeader('X-Pjax', 'Content');
        return Controller::curr()->redirect($url, 302);
    }
}
