<?php declare(strict_types=1);

namespace Myfav\Inquiry\Subscriber;

use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Myfav\Inquiry\Event\InquirySendEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;


class BusinessEventSubscriber implements EventSubscriberInterface
{
    private BusinessEventCollector $businessEventCollector;

    public function __construct(BusinessEventCollector $businessEventCollector) {
        $this->businessEventCollector = $businessEventCollector;
    }

    public static function getSubscribedEvents(): array
    {
        //die('fffff');
        return [
            BusinessEventCollectorEvent::NAME => ['onAddExampleEvent', 1000],
        ];
    }

    public function onAddExampleEvent(BusinessEventCollectorEvent $event): void
    {
        //die('sfsdffsfds');
        $collection = $event->getCollection();

        $definition = $this->businessEventCollector->define(InquirySendEvent::class);

        if (!$definition) {
            return;
        }

        $collection->set($definition->getName(), $definition);
    }
}
