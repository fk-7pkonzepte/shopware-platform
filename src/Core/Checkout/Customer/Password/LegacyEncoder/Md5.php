<?php
declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Password\LegacyEncoder;

use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;

#[Package('customer-order')]
class Md5 implements LegacyEncoderInterface
{
    /**
     * @deprecated tag:v6.7.0.0 - Method will be removed
     */
    public function getName(): string
    {
        Feature::triggerDeprecationOrThrow(
            'v6.7.0.0',
            Feature::deprecatedMethodMessage(self::class, __METHOD__, 'v6.7.0.0')
        );
        return 'Md5';
    }

    /**
     * @internal tag:v6.7.0.0
     */
    public static function getEncoderName(): string
    {
        return 'Md5';
    }

    public function isPasswordValid(string $password, string $hash): bool
    {
        if (mb_strpos($hash, ':') === false) {
            return hash_equals($hash, md5($password));
        }
        [$md5, $salt] = explode(':', $hash);

        return hash_equals($md5, md5($password . $salt));
    }
}
