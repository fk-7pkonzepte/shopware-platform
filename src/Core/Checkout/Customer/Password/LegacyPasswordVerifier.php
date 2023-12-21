<?php

declare(strict_types=1);

namespace Shopware\Core\Checkout\Customer\Password;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Checkout\Customer\CustomerException;
use Shopware\Core\Framework\Feature;
use Shopware\Core\Framework\Log\Package;
use Symfony\Component\DependencyInjection\ServiceLocator;

#[Package('customer-order')]
class LegacyPasswordVerifier
{
    /**
     * @internal
     */
    public function __construct(private readonly ServiceLocator $encoderLocator)
    {
    }

    /**
     * @deprecated tag:v6.7.0.0 - Remove code block with !Feature::isActive.
     */
    public function verify(string $password, CustomerEntity $customer): bool
    {
        $legacyEncoder = $customer->getLegacyEncoder();
        if (!$legacyEncoder || !$customer->getLegacyPassword()) {
            throw CustomerException::badCredentials();
        }

        if ($this->encoderLocator->has($legacyEncoder)) {
            $encoder = $this->encoderLocator->get($legacyEncoder);
            return $encoder->isPasswordValid($password, $customer->getLegacyPassword());
        }

        if (!Feature::isActive('v6.7.0.0')) {
            Feature::triggerDeprecationOrThrow('v6.7.0.0', 'Encoder evaluation deprecated');
            foreach ($this->encoderLocator->getProvidedServices() as $encoderName => $encoderType) {
                $encoder = $this->encoderLocator->get($encoderName);
                if ($encoder->getName() !== $legacyEncoder) {
                    continue;
                }

                return $encoder->isPasswordValid($password, $customer->getLegacyPassword());
            }
        }

        throw CustomerException::legacyPasswordEncoderNotFound($legacyEncoder);
    }
}
