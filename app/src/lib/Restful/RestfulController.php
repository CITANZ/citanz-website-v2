<?php

namespace Leochenftw\Restful;

use SilverStripe\Control\Director;
use SilverStripe\Core\Config\Config;
use Exception;
use SilverStripe\Control\HTTPRequest;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Control\Controller;
use Leochenftw\Debugger;
use SilverStripe\Security\Member;

abstract class RestfulController extends Controller
{
    private $logger =   null;
    private static $allowed_actions = [
        'options'   =>  true,
        'head'      =>  true
    ];

    public function init()
    {
        parent::init();

        // check for https
        if($this->config()->https_only && !Director::is_https()) {
            $response   =   $this->getResponse();
            $response->setStatusCode('403', 'http request not allowed');
            $response->setBody("Request over HTTP is not allowed. Please switch to https.");
            $response->output();
            exit;
        }

        // check for CORS options request
        if ($this->request->httpMethod() === 'OPTIONS' ) {
            // create direct response without requesting any controller
            $response   =   $this->getResponse();
            // set CORS header from config
            $response   =   $this->addCORSHeaders($response);
            $response->output();
            exit;
        }
    }

    public function head(HTTPRequest $request)
    {
        if(method_exists($this, 'get')) {
            $result = $this->get($request);
            if($result instanceof HTTPResponse) {
                $result->setBody(null);
                return $result;
            }
            return null;
        }
        throw new RestUserException("Endpoint doesn't have a GET implementation", 404);
    }

    protected function handleAction($request, $action)
    {
        foreach($request->latestParams() as $k => $v) {
            if($v || !isset($this->urlParams[$k])) $this->urlParams[$k] = $v;
        }
        // set the action to the request method / for developing we could use an additional parameter to choose another method
        $action = $this->getMethodName($request);
        $this->action = $action;
        $this->requestParams = $request->requestVars();
        $className = __CLASS__;
        // create serializer
        $serializer = SerializerFactory::create_from_request($request);
        $response = $this->getResponse();
        // perform action
        try {
            if(!$this->hasAction($action)) {
                // method couldn't found on controller
                throw new RestUserException("Action '$action' isn't available on class $className.", 404);
            }
            if(!$this->checkAccessAction($action)) {
                throw new RestUserException("Action '$action' isn't allowed on class $className.", 404, 401);
            }
            $actionResult = null;
            if(method_exists($this, 'beforeCallActionHandler')) {
                // call before action hook
                $actionResult = $this->beforeCallActionHandler($request, $action);
            }
            // if action hook contains data it will be used as result, otherwise the action handler will be called
            if(!$actionResult) {
                // perform action
                $actionResult = $this->$action($request);
            }
            $body = $actionResult;
        } catch(RestUserException $ex) {
            // a user exception was caught
            $response->setStatusCode($ex->getHttpStatusCode());
            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];
        } catch(RestSystemException $ex) {
            // a system exception was caught
            $response->addHeader('Content-Type', $serializer->contentType());
            $response->setStatusCode($ex->getHttpStatusCode());
            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];
            if(Director::isDev()) {
                $body = array_merge($body, [
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTrace()
                ]);
            }
        } catch(Exception $ex) {
            // an unexpected exception was caught
            $response->addHeader('Content-Type', $serializer->contentType());
            $response->setStatusCode("500");
            $body = [
                'message' => $ex->getMessage(),
                'code' => $ex->getCode()
            ];
            if(Director::isDev()) {
                $body = array_merge($body, [
                    'file' => $ex->getFile(),
                    'line' => $ex->getLine(),
                    'trace' => $ex->getTrace()
                ]);
            }
        }
        // serialize content and set body of response
        $response->addHeader('Content-Type', $serializer->contentType());
        // TODO: body could be an exception; check it before the response is generated
        $response->setBody($serializer->serialize($body));
        // set CORS header from config
        $response = $this->addCORSHeaders($response);
        return $response;
    }

    private function getMethodName($request)
    {
        $method = '';
        if(Director::isDev() && ($varMethod = $request->getVar('method'))) {
            if(in_array(strtoupper($varMethod), ['GET','POST','PUT','DELETE','HEAD', 'PATCH'])) {
                $method = $varMethod;
            }
        } else {
            $method = $request->httpMethod();
        }
        return strtolower($method);
    }

    protected function isAuthenticated()
    {
        return $this->currentUser() ? true : false;
    }

    protected function isAdmin()
    {
        $member = $this->currentUser();
        return $member && \Injector::inst()->get('PermissionChecks')->isAdmin($member);
    }

    protected function addCORSHeaders($response)
    {
        $default_origin     =   $this->config()->CORSOrigin;
        $allowed_origins    =   $this->config()->CORSOrigins;

        if (in_array($this->request->getHeader('origin'), $allowed_origins)) {
            $response->addHeader('Access-Control-Allow-Origin', $this->request->getHeader('origin'));
        } else {
            $response->addHeader('Access-Control-Allow-Origin', $default_origin);
        }

        $response->addHeader('Access-Control-Allow-Methods', $this->config()->CORSMethods);
        $response->addHeader('Access-Control-Max-Age', $this->config()->CORSMaxAge);
        $response->addHeader('Access-Control-Allow-Headers', $this->config()->CORSAllowHeaders);
        if ($this->config()->CORSAllowCredentials) {
            $response->addHeader('Access-Control-Allow-Credentials', 'true');
        }
        return $response;
    }

    protected function currentUser() {
        return Member::currentUser();
    }
}
