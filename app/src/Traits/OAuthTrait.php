<?php

namespace App\Web\Traits;


use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;

use League\OAuth2\Server\ResourceServer;
use League\OAuth2\Server\Exception\OAuthServerException;

use Robbie\Psr7\HttpRequestAdapter;
use Robbie\Psr7\HttpResponseAdapter;

use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;

use SilverStripe\Core\Injector\Injector;
use Psr\Log\LoggerInterface;

use App\Web\Model\OAuth\Repository\AccessTokenRepository;
use App\Web\Service\EncryptionCertificateService;
use Cita\eCommerce\Model\Customer;

trait OAuthTrait
{
    public function authenticate()
    {
        $accessTokenRepository = new AccessTokenRepository();
        $publicKeyPath = EncryptionCertificateService::getPublicKey();

        $server = new ResourceServer(
            $accessTokenRepository,
            $publicKeyPath
        );

        $ssRequest = $this->getRequest();

        // fixing an odd bug that REDIRECT_HTTP_AUTHORIZATION is not recognise as HTTP_AUTHORIZATION
        if (empty($ssRequest->getHeader('authorization')) && !empty($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
            $ssRequest->addHeader('authorization', $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        }

        $psr7Request = (new HttpRequestAdapter())->toPsr7($ssRequest);
        $psr7Response = (new HttpResponseAdapter())->toPsr7(new HTTPResponse());

        try {
            $server->validateAuthenticatedRequest($psr7Request);

            $authHeader = $this->getRequest()->getHeader('authorization');
            $jwt = trim((string) preg_replace('/^(?:\s+)?Bearer\s/', '', $authHeader));

            try {
                $config = Configuration::forSymmetricSigner(
                    new Sha256(),
                    InMemory::base64Encoded(Environment::getEnv('OAUTH_ENCRYPTION_KEY'))
                );

                $token = $config->parser()->parse($jwt);
                $userId = $token->claims()->get('sub');

                if (!$userId) {
                    return false;
                }

                return Customer::get()->filter('GUID', $userId)->first();
            } catch (\Exception $e) {
                Injector::inst()->get(LoggerInterface::class)->error($e);

                return false;
            }
        } catch (OAuthServerException $e) {
            $psr7Response = $e->generateHttpResponse($psr7Response);
            Injector::inst()->get(LoggerInterface::class)->error($e->getHint());

            return (new HttpResponseAdapter())->fromPsr7($psr7Response);
        } catch (\Exception $e) {
            $psr7Response = (new OAuthServerException($e->getMessage(), 0, 'unknown_error', 500))
                ->generateHttpResponse($psr7Response)
            ;

            Injector::inst()->get(LoggerInterface::class)->error('Exception');

            return (new HttpResponseAdapter())->fromPsr7($psr7Response);
        }
    }
}
