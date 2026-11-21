<?php

namespace App\Web\Email;

use Aws\Credentials\CredentialProvider;
use Aws\Ses\SesClient;
use SilverStripe\Core\Injector\Factory;

/**
 * Builds the SES client with the EC2 instance role first.
 *
 * The SDK's default chain reads AWS_ACCESS_KEY_ID / AWS_SECRET_ACCESS_KEY
 * from the environment before the instance role. Production's .env holds
 * such a pair for CV uploads (a different IAM user, possibly another
 * account), so with the default chain mail would be signed with those keys
 * and fail. Here the instance role is tried first; the default chain is only
 * a fallback for running outside EC2 (local tests with your own credentials).
 */
class SesClientFactory implements Factory
{
    public function create($service, array $params = [])
    {
        $config = $params[0] ?? $params['configuration'] ?? [];
        $config['credentials'] = CredentialProvider::memoize(
            CredentialProvider::chain(
                CredentialProvider::instanceProfile(['timeout' => 1, 'retries' => 1]),
                CredentialProvider::defaultProvider()
            )
        );
        return new SesClient($config);
    }
}
