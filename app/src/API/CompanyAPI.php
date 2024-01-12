<?php

namespace App\Web\API;
use SilverStripe\Dev\Debug;
use SilverStripe\Core\Convert;
use SilverStripe\Control\HTTPResponse;
use Leochenftw\Restful\RestfulController;
use App\Web\Traits\OAuthTrait;
use App\Web\Model\Company;

class CompanyAPI extends RestfulController
{
    use OAuthTrait;

    private $user = null;
    /**
     * Defines methods that can be called directly.
     *
     * @var array
     */
    private static $allowed_actions = [
        'get' => true,
    ];

    public function get($request)
    {
        $this->user = $this->authenticate();

        if (!$this->user || $this->user instanceof HTTPResponse) {
            return $this->httpError(401, 'Unauthorised');
        }

        if ($action = $request->param('action')) {
            return $this->$action($request);
        } elseif ($term = $request->requestVar('q')) {
            return $this->getCompanyList($term);
        }

        return $this->httpError(404);
    }

    private function getCompanyList($term)
    {
        $term = trim($term);

        if (!empty($term)) {
            $list = Company::get()
                ->filter(['Title:StartsWith' => $term])
                ->limit(10)
                ->toArray()
            ;

            return array_map(fn($item) => [
                'id' => $item->ID, 
                'title' => $item->Title,
                'website' => $item->Link()->exists() ? $item->Link()->LinkURL : null,
            ], $list);
        }

        return $this->httpError(400, 'Missing search term');
    }
}
