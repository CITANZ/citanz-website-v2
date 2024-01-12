<?php

namespace App\Web\JobReferral\API;

use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;
use SilverStripe\Core\Environment;
use App\Web\JobReferral\Model\ReferralOpportunity;
use App\Web\Model\Company;

class ReferralOpportunityAPI extends RestfulController
{
    use OAuthTrait;

    CONST ALLOWED_TAGS = '<p><ul><ol><li><img><a><strong><table><tr><td><dl><dt><dd><em><u><b><i><span>';

    private const ANONYMOUS_METHODS = [
        'createJobOpportunity',
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
        'delete' => true,
        'put' => true,
    ];

    public function put($request)
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

    public function delete($request)
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

    public function post($request)
    {
        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        $action = $request->param('action');

        if (in_array($action, static::ANONYMOUS_METHODS)) {
            return $this->$action($request);
        }

        return $this->httpError(401, 'Unauthorised');
    }

    private function updateJobOpportunity(&$request)
    {
        try {
            $data = json_decode($request->getBody());
            if ($id = $request->param('ID')) {
                $id = (int) str_replace('referral-opportunity-', '', $id);
                if ($listing = $this->user->ListedJobs()->byID($id)) {
                    $companyId = (int) $data->companyId;
                    $companyTitle = $data->companyTitle;
                    $title = trim($data->job_title);
                    $desc = trim($data->job_desc);
                    $faqs = $data->faqs;
                    $until = $data->until;

                    if (empty($title) || empty($desc)) {
                        return $this->httpError(400, '你提交啥了？啥也没有噢 - 再想想');
                    }
            
                    if (empty($companyId) && empty($companyTitle)) {
                        return $this->httpError(400, '招聘企业不能为空！');
                    } 
                    
                    if (empty($companyId) && !empty($companyTitle)) {
                        $company = Company::create()->update([
                            'Title' => $companyTitle
                        ]);
            
                        $company->write();
                        $companyId = $company->ID;
                    }

                    
                    // Sanitize the HTML string
                    $sanitisedJobDesc = strip_tags($desc, static::ALLOWED_TAGS);

                    $listing->update([
                        'JobTitle' => $title,
                        'JobDescription' => $sanitisedJobDesc,
                        'FAQs' => $faqs,
                        'CompanyID' => $companyId,
                        'PostedByID' => $this->user->ID,
                    ])->write();
                    
                    return [
                        'message' => 'Job listing has been updated',
                    ];
                }
    
                return $this->httpError(403, 'You do not have permission to update this listing');
            }

            return $data;
        } catch (\Exception $e) {
            return $this->httpError(400);
        }
    }

    private function deleteListing(&$request)
    {
        if ($id = $request->param('ID')) {
            $id = (int) str_replace('referral-opportunity-', '', $id);
            if ($listing = $this->user->ListedJobs()->byID($id)) {
                $listing->delete();
                return [
                    'message' => 'Job listing has been removed',
                ];
            }

            return $this->httpError(403, 'You do not have permission to delete this listing');
        }

        return $this->httpError(400, 'Missing listing id');
    }

    private function getListingDetails(&$request)
    {
        if ($id = $request->param('ID')) {
            $id = (int) str_replace('referral-opportunity-', '', $id);
            return ReferralOpportunity::get()->byID($id)->Data;
        }

        return $this->httpError(400, 'Missing listing id');
    }

    private function createJobOpportunity(&$request)
    {
        if (!$this->user->CanCreateReferralOpportunities) {
            return $this->httpError(403, '声望不够，不可执行此操作。请先到微信群里找CITA管理层聊聊。');
        }

        $companyId = (int) $request->postVar('companyId');
        $companyTitle = $request->postVar('companyTitle');
        $title = trim($request->postVar('job_title'));
        $desc = trim($request->postVar('job_desc'));
        $faqs = $request->postVar('faqs');
        $until = $request->postVar('until');

        if (empty($title) || empty($desc)) {
            return $this->httpError(400, '你提交啥了？啥也没有噢 - 再想想');
        }

        if (empty($companyId) && empty($companyTitle)) {
            return $this->httpError(400, '招聘企业不能为空！');
        } 
        
        if (empty($companyId) && !empty($companyTitle)) {
            $company = Company::create()->update([
                'Title' => $companyTitle
            ]);

            $company->write();
            $companyId = $company->ID;
        }

        if ($job = $this->user->JobApplications()->sort('Created DESC')->first()) {
            $diff = time() - strtotime($job);
            if ($diff < 10) {
                return $this->httpError(400, '刷那么快干嘛？');
            }
        }

        
        // Sanitize the HTML string
        $sanitisedJobDesc = strip_tags($desc, static::ALLOWED_TAGS);

        $job = ReferralOpportunity::create()->update([
            'JobTitle' => $title,
            'JobDescription' => $sanitisedJobDesc,
            'FAQs' => $faqs,
            'CompanyID' => $companyId,
            'PostedByID' => $this->user->ID,
        ]);

        if (!empty($until)) {
            $job->ValidUntil = strtotime($until);
        }

        $job->write();

        return $job;
    }
}
