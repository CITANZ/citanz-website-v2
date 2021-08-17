<?php

namespace App\Web\Model\OAuth\Entity;

use League\OAuth2\Server\Entities\ClientEntityInterface;
use League\OAuth2\Server\Entities\Traits\ClientTrait;
use League\OAuth2\Server\Entities\Traits\EntityTrait;

use function explode;

class ClientEntity implements ClientEntityInterface
{
    use ClientTrait, EntityTrait, RevokableTrait;

    protected $secret;

    protected $personalAccessClient;

    protected $passwordClient;

    public function __construct(string $identifier, string $name, string $redirectUri)
    {
        $this->setIdentifier($identifier);
        $this->name = $name;
        $this->redirectUri = explode(',', $redirectUri);
    }

    public function getSecret()
    {
        return $this->secret;
    }

    public function setSecret(string $secret)
    {
        $this->secret = $secret;
    }

    public function hasPersonalAccessClient()
    {
        return $this->personalAccessClient;
    }

    public function setPersonalAccessClient(bool $personalAccessClient)
    {
        $this->personalAccessClient = $personalAccessClient;
    }

    public function hasPasswordClient()
    {
        return $this->passwordClient;
    }

    public function setPasswordClient(bool $passwordClient)
    {
        $this->passwordClient = $passwordClient;
    }

     public function isConfidential()
     {
        return true;
     }
}
