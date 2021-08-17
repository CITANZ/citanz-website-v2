<?php

namespace App\Web\Model\OAuth\Repository;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Repositories\ScopeRepositoryInterface;

use App\Web\Model\OAuth\Entity\ScopeEntity;
use App\Web\Model\OAuth\Scope;

class ScopeRepository extends AbstractRepository implements ScopeRepositoryInterface
{
    public function getScopeEntityByIdentifier($identifier)
    {
        $scope = Scope::get()->filter('GUID', $identifier)->first();

        if (!$scope) {
            return;
        }

        $entity = new ScopeEntity();
        $entity->setIdentifier($scope->GUID);

        return $scope;
    }

    public function finalizeScopes(array $scopes, $grantType, ClientEntityInterface $clientEntity, $userIdentifier = null)
    {
        return $scopes;
    }
}
