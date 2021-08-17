<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Entities\AuthCodeEntityInterface;
use League\OAuth2\Server\Exception\UniqueTokenIdentifierConstraintViolationException;
use League\OAuth2\Server\Repositories\AuthCodeRepositoryInterface;

use App\Web\Model\OAuth\Entity\AuthCodeEntity;
use Cita\eCommerce\Model\Customer;
use App\Web\Model\OAuth\Client;
use App\Web\Model\OAuth\AuthCode;

class AuthCodeRepository extends AbstractRepository implements AuthCodeRepositoryInterface
{
    public function getNewAuthCode()
    {
        return new AuthCodeEntity();
    }

    public function persistNewAuthCode(AuthCodeEntityInterface $authCodeEntity)
    {
        $user = Customer::get()->filter('GUID', $authCodeEntity->getUserIdentifier())->first();
        $client = Customer::get()->filter('GUID', $authCodeEntity->getClient()->getIdentifier())->first();

        $authCode = new AuthCode();
        $authCode->GUID = $authCodeEntity->getIdentifier();
        $authCode->UserID = $user ? $user->ID : null;
        $authCode->ClientID = $client ? $client->ID : null;
        $authCode->Scopes = $this->scopesToString($authCodeEntity->getScopes());
        $authCode->Revoked = false;
        $authCode->ExpiresAt = date(
            \DateTime::ISO8601,
            $authCodeEntity->getExpiryDateTime()->getTimestamp()
        );

        try {
            $authCode->write();
        } catch (\Exception $e) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function revokeAuthCode($codeId)
    {
        $authCode = AuthCode::get()->filter('GUID', $codeId)->first();

        if (!$authCode) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }

        $authCode->Revoked = true;

        try {
            $authCode->write();
        } catch (\Exception $e) {
            throw UniqueTokenIdentifierConstraintViolationException::create();
        }
    }

    public function isAuthCodeRevoked($codeId)
    {
        $authCode = AuthCode::get()->filter('GUID', $codeId)->first();

        if (!$authCode) {
            return false;
        }

        return (bool) $authCode->Revoked;
    }
}
