<?php

namespace App\Web\API;

use App\Web\Model\OAuth\Repository\AccessTokenRepository;
use App\Web\Model\OAuth\Repository\ClientRepository;
use App\Web\Model\OAuth\Repository\RefreshTokenRepository;
use App\Web\Model\OAuth\Repository\ScopeRepository;
use App\Web\Model\OAuth\Repository\UserRepository;
use App\Web\Model\User;
use App\Web\Service\EncryptionCertificateService;
use Lcobucci\JWT\Configuration;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use League\OAuth2\Server\Grant\ClientCredentialsGrant;
use League\OAuth2\Server\Grant\PasswordGrant;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use League\OAuth2\Server\ResourceServer;
use Leochenftw\Restful\RestfulController;
use Psr\Log\LoggerInterface;
use Robbie\Psr7\HttpRequestAdapter;
use Robbie\Psr7\HttpResponseAdapter;
use SilverStripe\Control\HTTPResponse;
use SilverStripe\Core\Environment;
use SilverStripe\Core\Injector\Injector;
use App\Web\Traits\OAuthTrait;

class Authorise extends RestfulController
{
    use OAuthTrait;
    /**
     * Defines methods that can be called directly.
     *
     * @var array
     */
    private static $allowed_actions = [
        'post' => true,
    ];

    public function post($request)
    {
        $server = $this->getAuthServer();
        $psr7Request = (new HttpRequestAdapter())->toPsr7($request);
        $response = (new HttpResponseAdapter())->toPsr7(new HTTPResponse());

        try {
            $psr7Response = $server->respondToAccessTokenRequest($psr7Request, $response);

            return json_decode((new HttpResponseAdapter())->fromPsr7($psr7Response)->getBody());
        } catch (OAuthServerException $e) {
            return $this->httpError(401, 'Incorrect email or password.');
        } catch (\Exception $e) {
            return $this->httpError(500, $e->getMessage());
        }
    }

    private function getAuthServer()
    {
        $encryptionKey = Environment::getEnv('OAUTH_ENCRYPTION_KEY');

        if (!$encryptionKey) {
            throw new \RuntimeException('Please set OAUTH_ENCRYPTION_KEY in .env file');
        }

        $privateKey = EncryptionCertificateService::getPrivateKey();

        $clientRepository = new ClientRepository();
        $scopeRepository = new ScopeRepository();
        $accessTokenRepository = new AccessTokenRepository();
        $refreshTokenRepository = new RefreshTokenRepository();
        $userRepository = new UserRepository();

        $server = new AuthorizationServer(
            $clientRepository,
            $accessTokenRepository,
            $scopeRepository,
            $privateKey,
            $encryptionKey
        );

        $passwordGrant = new PasswordGrant(
            $userRepository,
            $refreshTokenRepository
        );

        $passwordGrant->setRefreshTokenTTL(new \DateInterval('P1M'));

        $refreshTokenGrant = new RefreshTokenGrant(
            $refreshTokenRepository
        );

        $refreshTokenGrant->setRefreshTokenTTL(new \DateInterval('P1M'));

        $server->enableGrantType(
            new ClientCredentialsGrant(),
            new \DateInterval('PT4H')
        );

        $server->enableGrantType(
            $passwordGrant,
            new \DateInterval('PT4H')
        );

        $server->enableGrantType(
            $refreshTokenGrant,
            new \DateInterval('PT4H')
        );

        return $server;
    }
}
