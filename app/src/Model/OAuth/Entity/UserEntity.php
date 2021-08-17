<?php

namespace App\Web\Model\OAuth\Entity;

use League\OAuth2\Server\Entities\Traits\EntityTrait;
use League\OAuth2\Server\Entities\UserEntityInterface;

class UserEntity implements UserEntityInterface
{
    use EntityTrait;

    public function __construct($identifier)
    {
        $this->setIdentifier($identifier);
    }
}
