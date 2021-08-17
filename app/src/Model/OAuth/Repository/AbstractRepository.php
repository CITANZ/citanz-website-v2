<?php

namespace App\Web\Model\OAuth\Repository;

class AbstractRepository
{
    protected function scopesToString(array $scopes) : string
    {
        if (empty($scopes)) {
            return '';
        }

        return trim(array_reduce($scopes, function ($result, $item) {
            return $result . ' ' . $item->getIdentifier();
        }));
    }
}
