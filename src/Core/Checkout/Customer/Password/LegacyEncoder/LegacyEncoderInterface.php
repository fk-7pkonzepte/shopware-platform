<?php
declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Password\LegacyEncoder;

use Shopware\Core\Framework\Log\Package;

/**
 * @internal tag:v6.7.0.0
 * Register encoder name for locator by static method 'getEncoderName' (like in Md5) or by adding key attribute with name to tag node of encoder service (like in Sha256).
 */
#[Package('customer-order')]
interface LegacyEncoderInterface
{
    /**
     * @deprecated tag:v6.7.0.0 - Method will be removed, use static method 'getEncoderName' instead or add name as key attribute to the tag of this service.
     */
    public function getName(): string;

    public function isPasswordValid(string $password, string $hash): bool;
}
