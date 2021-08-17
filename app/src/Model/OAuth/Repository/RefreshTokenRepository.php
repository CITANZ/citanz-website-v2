<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Entities\RefreshTokenEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\RefreshTokenRepositoryInterface;

use App\Web\Model\OAuth\Entity\RefreshTokenEntity;
use App\Web\Model\OAuth\RefreshToken;
use App\Web\Model\OAuth\AccessToken;

class RefreshTokenRepository extends AbstractRepository implements RefreshTokenRepositoryInterface
{
    public function getNewRefreshToken()
    {
        return new RefreshTokenEntity();
    }

    public function persistNewRefreshToken(RefreshTokenEntityInterface $refreshTokenEntity)
    {
        $accessToken = AccessToken::get()
            ->filter('GUID', $refreshTokenEntity->getAccessToken()->getIdentifier())
            ->first();

        $refreshToken = new RefreshToken();
        $refreshToken->GUID = $refreshTokenEntity->getIdentifier();
        $refreshToken->AccessTokenID = $accessToken ? $accessToken->ID : null;
        $refreshToken->Revoked = false;
        $refreshToken->ExpiresAt = date(
            \DateTime::ISO8601,
            $refreshTokenEntity->getExpiryDateTime()->getTimestamp()
        );

        try {
            $refreshToken->write();
        } catch (\Exception $e) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function revokeRefreshToken($tokenId)
    {
        $refreshToken = RefreshToken::get()->filter('GUID', $tokenId)->first();

        if (!$refreshToken) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $refreshToken->Revoked = true;
        $refreshToken->write();
    }

    public function isRefreshTokenRevoked($tokenId)
    {
        $refreshToken = RefreshToken::get()->filter('GUID', $tokenId)->first();

        if (!$refreshToken) {
            return false;
        }

        return (bool) $refreshToken->Revoked;
    }
}
