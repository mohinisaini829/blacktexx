<?php

namespace Myfav\Zweideh\Storefront\Subscriber;

use Shopware\Core\Checkout\Cart\Event\BeforeLineItemAddedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class LineItemAddedSubscriber implements EventSubscriberInterface {
    private RequestStack $requestStack;

    /**
     * __construct
     *
     * @param  RequestStack $requestStack
     * @return void
     */
    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    /**
     * getSubscribedEvents
     *
     * @return void
     */
    public static function getSubscribedEvents()
    {
        return [
            BeforeLineItemAddedEvent::class => 'beforeLineItemAdded'
        ];
    }

    /**
     * onLineItemAdded
     *
     * @param  mixed $event
     * @return void
     */
    public function beforeLineItemAdded(BeforeLineItemAddedEvent $event)
    {
        $request = $this->requestStack->getCurrentRequest();
        $lumise_tmp_key = $request->request->get('lumise_tmp_key');
        $lumise_tmp_cart_id = $request->request->get('lumise_tmp_cart_id');
        
        $lineItem = $event->getLineItem();
        $lineItem->setPayloadValue('lumise_tmp_key', $lumise_tmp_key);
        $lineItem->setPayloadValue('lumise_tmp_cart_id', $lumise_tmp_cart_id);
    }
}
