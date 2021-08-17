<?php

namespace App\Web\Model\OAuth\Entity;

trait RevokableTrait
{
    protected $revoked;

    public function isRevoked()
    {
        return $this->revoked;
    }


    public function setRevoked(bool $revoked)
    {
        $this->revoked = $revoked;
    }
}
