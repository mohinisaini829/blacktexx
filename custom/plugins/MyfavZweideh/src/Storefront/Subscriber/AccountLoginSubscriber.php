<?php

namespace Myfav\Zweideh\Storefront\Subscriber;

use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;

class AccountLoginSubscriber implements EventSubscriberInterface {
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
            AccountLoginPageLoadedEvent::class => 'onAccountLoginPageLoaded'
        ];
    }

    /**
     * onLineItemAdded
     *
     * @param  mixed $event
     * @return void
     */
    public function onAccountLoginPageLoaded(AccountLoginPageLoadedEvent $event)
    {
        //$request = $this->requestStack->getCurrentRequest();

        if (!isset($_GET['redirectParameters'])) {
            return;
        }

        $redirectParameters = $_GET['redirectParameters'];
        $redirectParameters = json_decode($redirectParameters, true);

        if(null === $redirectParameters) {
            return;
        }

        if(!isset($redirectParameters['designKey'])) {
            return;
        }

        if(isset($redirectParameters['tmpCartId'])) {
            $designKey = $redirectParameters['designKey'];
            $tmpCartId = $redirectParameters['tmpCartId'];

            // Artikeldaten laden.
            $page = $event->getPage();
            $page->addExtension('myfavZweideh', new ArrayEntity([
                'designKey' => $designKey,
                'tmpCartId' => $tmpCartId
            ]));
        }
    }
}
