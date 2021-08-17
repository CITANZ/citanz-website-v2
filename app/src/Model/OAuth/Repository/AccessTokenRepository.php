<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Entities\AccessTokenEntityInterface;
use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AccessTokenRepositoryInterface;

use Ramsey\Uuid\Uuid;

use App\Web\Model\OAuth\Entity\AccessTokenEntity;
use Cita\eCommerce\Model\Customer;
use App\Web\Model\OAuth\AccessToken;
use App\Web\Model\OAuth\Client;

class AccessTokenRepository extends AbstractRepository implements AccessTokenRepositoryInterface
{
    public function getNewToken(ClientEntityInterface $clientEntity, array $scopes, $userIdentifier = null)
    {
        $accessToken = new AccessTokenEntity();

        $accessToken->setClient($clientEntity);

        foreach ($scopes as $scope) {
            $accessToken->addScope($scope);
        }

        $accessToken->setUserIdentifier($userIdentifier);

        return $accessToken;
    }

    public function persistNewAccessToken(AccessTokenEntityInterface $accessTokenEntity)
    {
        $user = Customer::get()->filter('GUID', $accessTokenEntity->getUserIdentifier())->first();
        $client = Client::get()->filter('GUID', $accessTokenEntity->getClient()->getIdentifier())->first();

        $accessToken = new AccessToken();
        $accessToken->GUID = $accessTokenEntity->getIdentifier();
        $accessToken->UserID = $user ? $user->ID : null;
        $accessToken->ClientID = $client ? $client->ID : null;
        $accessToken->Scopes = $this->scopesToString($accessTokenEntity->getScopes());
        $accessToken->Revoked = false;
        $accessToken->ExpiresAt = date(
            \DateTime::ISO8601,
            $accessTokenEntity->getExpiryDateTime()->getTimestamp()
        );

        try {
            $accessToken->write();
        } catch (\Exception $e) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function revokeAccessToken($tokenId)
    {
        $accessToken = AccessToken::get()->filter('GUID', $tokenId)->first();

        if (!$accessToken) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $accessToken->Revoked = true;
        $accessToken->write();
    }

    public function isAccessTokenRevoked($tokenId)
    {
        $accessToken = AccessToken::get()->filter('GUID', $tokenId)->first();

        if (!$accessToken) {
            return false;
        }

        return (bool) $accessToken->Revoked;
    }
}
