<?php

namespace App\Web\API;

use Leochenftw\Restful\RestfulController;
use SilverStripe\SiteConfig\SiteConfig;
use SilverStripe\Core\Config\Config;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\ClientException;
use Leochenftw\Debugger;

class SubscriptionAPI extends RestfulController
{
    /**
     * Defines methods that can be called directly
     * @var array
     */
    private static $allowed_actions = [
        'post'  =>  true
    ];

    private function is_valid($val)
    {
        return !empty($val) && $val != 'null';
    }

    public function post($request)
    {
        $config     =   SiteConfig::current_site_config()->get_mailchimp_config();
        $endpoint   =   $config['endpoint'];
        $key        =   $config['api_key'];

        if ($this->is_valid($request->postVar('firstname')) && $this->is_valid($request->postVar('lastname')) && $this->is_valid($request->postVar('email'))) {
            try {
                $client     =   new Client();
                $response   =   $client->request(
                    'POST',
                    $endpoint,
                    [
                        'headers'   =>  [
                            'Authorization' => "apikey {$key}"
                        ],
                        'json'   =>  [
                            'email_address' =>  $request->postVar('email'),
                            'status'        =>  'subscribed',
                            'merge_fields'  =>  [
                                "FNAME" =>  $request->postVar('firstname'),
                                "LNAME" =>  $request->postVar('lastname')
                            ]
                        ]
                    ]
                );

                return 'Thank you! We have received your contact information, and will keep you in the loop for any update from CITANZ.';
            } catch (ClientException $e) {
                $error  =   json_decode($e->getResponse()->getBody()->getContents());
                if ($error->title != 'Member Exists') {
                    return $this->httpError(401, '<p>Because you unsubscribed from our list previously, you are now required to do it via <a target="_blank" href="' . $config['fallback_url'] . '">Mailchimp\'s awesome signup form</a></p>');
                }
                return $this->httpError(400, 'You have already subscribed to our newsletter 😁');
            }
        }

        return $this->httpError(400, 'You wish 🙃');
    }
}
