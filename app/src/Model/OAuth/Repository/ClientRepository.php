<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Repositories\ClientRepositoryInterface;
use App\Web\Model\OAuth\Entity\ClientEntity;
use App\Web\Model\OAuth\Client;

class ClientRepository extends AbstractRepository implements ClientRepositoryInterface
{
    public function getClientEntity($clientIdentifier)
    {
        $client = Client::get()->filter('GUID', $clientIdentifier)->first();

        if (!$client) {
            return;
        }

        if (empty($client->Redirect)) {
            $redirect = '';
        } else {
            $redirect = $client->Redirect;
        }

        return new ClientEntity($clientIdentifier, $client->Name, $redirect);
    }

    public function validateClient($clientIdentifier, $clientSecret, $grantType)
    {
        $client = Client::get()->filter('GUID', $clientIdentifier)->first();

        if (!$client) {
            return false;
        }

        if ($client->Revoked) {
            return false;
        }

        if (!empty($clientSecret) && !password_verify($clientSecret, $client->Secret)) {
            return false;
        }

        $grantTypes = ['client_credentials', 'password', 'refresh_token', 'facebook'];

        if (!empty($grantType) && !in_array($grantType, $grantTypes)) {
            return false;
        }

        return true;
    }
}
