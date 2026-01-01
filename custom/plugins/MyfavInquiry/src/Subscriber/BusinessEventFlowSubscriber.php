<?php declare(strict_types=1);

namespace Myfav\Inquiry\Subscriber;

use Shopware\Core\Framework\Event\BusinessEventCollectorEvent;
use Myfav\Inquiry\Core\Framework\Event\TagAware;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class BusinessEventFlowSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            BusinessEventCollectorEvent::NAME => 'addTagAware',
        ];
    }

    public function addTagAware(BusinessEventCollectorEvent $event): void
    {
        //die('ddad');
        foreach ($event->getCollection()->getElements() as $definition) {
            $definition->addAware(TagAware::class);
        }
    }
}