<?php declare(strict_types=1);

namespace Myfav\Inquiry\BusinessEvent;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\BusinessEventCollector;
use Shopware\Core\Framework\Event\BusinessEventCollectorResponse;
use Myfav\Inquiry\Event\InquirySendEvent;

class InquirySendCollector extends BusinessEventCollector
{
    public function collect(Context $context): BusinessEventCollectorResponse
    {
        return new BusinessEventCollectorResponse([
            InquirySendEvent::class => [],  // No extra awareness needed here, as interfaces are implemented in event class
        ]);
    }
}
