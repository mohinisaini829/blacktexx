<?php declare(strict_types=1);

namespace Acris\CookieConsent\Subscriber;

use Shopware\Storefront\Framework\Routing\NotFound\NotFoundPageCacheKeyEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\ParameterBag;

class NotFoundCacheKeySubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents()
    {
        return [
            NotFoundPageCacheKeyEvent::class => 'onNotFoundPageCacheKeyEvent'
        ];
    }

    public function onNotFoundPageCacheKeyEvent(NotFoundPageCacheKeyEvent $notFoundPageCacheKeyEvent): void
    {
        $request = $notFoundPageCacheKeyEvent->getRequest();
        if(!empty($notFoundPageCacheKeyEvent->getKey()) && is_string($notFoundPageCacheKeyEvent->getKey()) && (($request->cookies->has('acris_cookie_acc') === true || $request->cookies->has('cookie-preference') === true))) {
            $keyParts = [];
            if($request->cookies->has('acris_cookie_acc') === true) $keyParts[] = $request->cookies->get('acris_cookie_acc');
            if($request->cookies->has('cookie-preference') === true) $keyParts[] = $request->cookies->get('cookie-preference');
            $key = json_encode($keyParts);
            if (empty($key)) return;

            $hashKey = md5($key);
            if (empty($hashKey)) return;

            $notFoundPageCacheKeyEvent->setKey($notFoundPageCacheKeyEvent->getKey() . $hashKey);
        }
    }
}
