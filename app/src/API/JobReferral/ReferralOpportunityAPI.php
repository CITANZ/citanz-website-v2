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
use App\Web\JobReferral\Model\JobApplication;
use App\Web\Email\JobApplicationNotification;
use Aws\S3\S3Client;
use Aws\S3\Exception\S3Exception;

class ReferralOpportunityAPI extends RestfulController
{
    use OAuthTrait;

    CONST ALLOWED_TAGS = '<p><ul><ol><li><img><a><strong><table><tr><td><dl><dt><dd><em><u><b><i><span>';

    private const ALLOWED_POST_METHODS = [
        'createJobOpportunity',
        'applyForReferralJob',
        'viewJobPage',
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

        if (in_array($action, static::ALLOWED_POST_METHODS)) {
            return $this->$action($request);
        }

        return $this->httpError(401, 'Unauthorised');
    }
    
    private function viewJobPage(&$request)
    {
        $id = $request->param('ID');
        if ($id && ($job = ReferralOpportunity::get()->byID($id))) {
            $job->TimesViewed += 1;
            $job->write();
        }
    }

    private function applyForReferralJob(&$request)
    {
        $jobId = (int) $request->postVar('JobId');

        if (empty($jobId)) {
            return $this->httpError(400, 'Missing job id');
        }

        $UseExistingCV = $request->postVar('UseExistingCV');
        $UseExistingCL = $request->postVar('UseExistingCL');
        $UseExistingCV = !empty($UseExistingCV ) ? filter_var($UseExistingCV, FILTER_VALIDATE_BOOLEAN) : false;
        $UseExistingCL = !empty($UseExistingCL ) ? filter_var($UseExistingCL, FILTER_VALIDATE_BOOLEAN) : false;
        $CV = $request->postVar('UploadedCV');
        $CL = $request->postVar('UploadedCL');

        if (
            (!$UseExistingCV && empty($CV))
            || (!$UseExistingCL && empty($CL))
        ) {
            return $this->httpError(400, 'Missing CV or cover letter');
        }

        $job = ReferralOpportunity::get()->byID($jobId);

        if (!$job->IsAppliable) {
            return $this->httpError(404, 'Job already expired: ' . $job->ValidUntil);
        }

        $application = JobApplication::create()->update([
            'CoverLetter' => $request->postVar(''),
            'CV' => $request->postVar(''),
            'FirstName' => $request->postVar('FirstName'),
            'LastName' => $request->postVar('LastName'),
            'Email' => $request->postVar('Email'),
            'LinkedIn' => $request->postVar('LinkedIn'),
            'WechatID' => $request->postVar('WechatID'),
            'Phone' => $request->postVar('Phone'),
            'Github' => $request->postVar('Github'),
            'ApplicantID' => $this->user->ID,
            'JobID' => $jobId,
        ]);

        $application->write();
        
        $email = JobApplicationNotification::create($application);
        
        if (!empty($CV)) {
            $email->addAttachment($CV['tmp_name'], $CV['name'], $CV['type']);
        }

        if (!empty($CL)) {
            $email->addAttachment($CL['tmp_name'], $CL['name'], $CL['type']);
        }

        $bucketBaseUrl = "https://citanz.s3.ap-southeast-2.amazonaws.com/{$this->user->GUID}/";

        if ($UseExistingCV && !empty($this->user->CV)) {
            $awsCV = $this->getS3FileData("{$this->user->GUID}/{$this->user->CV}");
            $email->addAttachmentFromData($awsCV['data'], $awsCV['name']);
        }
        
        if ($UseExistingCL && !empty($this->user->CoverLetter)) {
            $awsCL = $this->getS3FileData("{$this->user->GUID}/{$this->user->CoverLetter}");
            $email->addAttachmentFromData($awsCL['data'], $awsCL['name']);
        }

        $email->send();
    }

    private function getS3FileData($key)
    {
        $s3 = new S3Client([
            'region'  => Environment::getEnv('AWS_REGION'),
            'credentials' => [
                'key' => Environment::getEnv('AWS_ACCESS_KEY_ID'),
                'secret' => Environment::getEnv('AWS_SECRET_ACCESS_KEY'),
            ],
        ]);

        try {
            $result = $s3->GetObject([
                'Bucket' => Environment::getEnv('AWS_BUCKET_NAME'),
                'Key'    => $key,
            ]);

            $meta = $result->get('Metadata');

            return [
                'data' => (string) $result->get('Body'),
                'name' => $meta['original-filename'],
            ];
        } catch (S3Exception $e) {
            echo "There was an error uploading the file.\n";
        }
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
                    $workLocation = $data->work_location;
                    $wtr = (bool) $data->wtr;

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
                        'ValidUntil' => strtotime($until),
                        'WorkLocation' => $workLocation,
                        'WTR' => $wtr,
                    ])->write();
                    
                    return [
                        'message' => 'Job listing has been updated',
                    ];
                }
    
                return $this->httpError(403, 'You do not have permission to update this listing');
            }

            return $this->httpError(400, 'Missing job listing ID');
        } catch (\Exception $e) {
            return $this->httpError(400);
        }
    }

    private function deleteListing(&$request)
    {
        if ($id = $request->param('ID')) {
            $id = (int) str_replace('referral-opportunity-', '', $id);
            if ($listing = $this->user->ListedJobs()->byID($id)) {
                $listing->update(['Deleted' => true])->write();
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
        $workLocation = $request->postVar('work_location');
        $wtr = (bool) $request->postVar('wtr');

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
            'WorkLocation' => $workLocation,
            'WTR' => $wtr,
        ]);

        if (!empty($until)) {
            $job->ValidUntil = strtotime($until);
        }

        $job->write();

        return $job;
    }
}
