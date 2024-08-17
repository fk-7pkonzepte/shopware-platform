<?php

declare(strict_types=1);

namespace Shopware\Core\Framework\Routing;

use Shopware\Core\Framework\Log\Package;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Exception\JsonException;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
#[Package('core')]
class RequestJsonBodyResolver implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['resolveJsonBody', 255],
        ];
    }

    public function resolveJsonBody(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($request->getContentTypeFormat() === 'json') {
            try {
                $request->request->replace($request->toArray());
                $request->attributes->set(RequestJsonBodyResolver::class, true);
            } catch (JsonException) {
            }
        }
    }
}
