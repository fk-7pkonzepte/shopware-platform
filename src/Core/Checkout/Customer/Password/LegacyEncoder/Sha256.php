<?php declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Password\LegacyEncoder;

use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;

#[Package('customer-order')]
class Sha256 implements LegacyEncoderInterface
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
        return 'Sha256';
    }

    public function isPasswordValid(string $password, string $hash): bool
    {
        [$iterations, $salt] = explode(':', $hash);

        $verifyHash = $this->generateInternal($password, $salt, (int) $iterations);

        return hash_equals($hash, $verifyHash);
    }

    private function generateInternal(string $password, string $salt, int $iterations): string
    {
        $hash = '';
        for ($i = 0; $i <= $iterations; ++$i) {
            $hash = hash('sha256', $hash . $password . $salt);
        }

        return $iterations . ':' . $salt . ':' . $hash;
    }
}
