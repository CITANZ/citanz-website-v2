<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\UserRepositoryInterface;
use SilverStripe\Security\PasswordEncryptor;

use App\Web\Model\OAuth\Entity\UserEntity;
use Cita\eCommerce\Model\Customer;

class UserRepository extends AbstractRepository implements UserRepositoryInterface
{
    public function getUserEntityByUserCredentials($username, $password, $grantType, ClientEntityInterface $clientEntity)
    {
        $user = Customer::get()->filter('Email', $username)->first();

        if (!$user) {
            return;
        }

        $user->LastLoggedIn = date(\DateTime::ISO8601);
        $user->write();

        if (password_verify($password, $user->Password)) {
            return new UserEntity($user->GUID);
        }

        return;
    }
}
