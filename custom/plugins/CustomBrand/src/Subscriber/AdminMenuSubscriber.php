<?php declare(strict_types=1);

namespace CustomBrand\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminMenuSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // No admin menu events available in Shopware 6
        return [];
    }
}