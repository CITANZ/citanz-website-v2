<?php

namespace App\Web\Service;

use SilverStripe\Control\Director;

class EncryptionCertificateService
{
    public static function getPublicKey()
    {
        return static::getKey('public');
    }

    public static function getPrivateKey()
    {
        return static::getKey('private');
    }

    public static function getPublicKeyPath()
    {
        return str_replace('file://', '', static::getKey('public'));
    }

    public function getPrivateKeyPath()
    {
        return str_replace('file://', '', static::getKey('private'));
    }

    private static function getKey($type)
    {
        $path = BASE_PATH . '/app/certs/' . Director::get_environment_type() . '/' . $type . '.key';

        if (!file_exists($path)) {
            throw new \RuntimeException('Could not find key. ' . $path);
        }

        return 'file://' . $path;
    }
}
