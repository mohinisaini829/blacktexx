<?php declare(strict_types=1);

namespace Acris\CookieConsent\Subscriber;

use Acris\CookieConsent\Components\CookieService;
use Shopware\Core\Framework\Context;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class BeforeResponseSendSubscriber implements EventSubscriberInterface
{
    private CookieService $cookieService;

    public function __construct(CookieService $cookieService)
    {

        $this->cookieService = $cookieService;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['setFunctionalCookiesWithValue', -3000]
            ]
        ];
    }

    public function setFunctionalCookiesWithValue(ResponseEvent $responseEvent): void
    {
        $request = $responseEvent->getRequest();
        $response = $responseEvent->getResponse();

        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$salesChannelContext instanceof SalesChannelContext) {
            return;
        }

        if ($request->attributes->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID)) {
            $context = Context::createDefaultContext();
            $defaultCookies = $this->cookieService->getDefaultCookies($request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID), $context);
            $requestCookies = $request->cookies->all();

            if ($defaultCookies->count() > 0) {
                foreach ($defaultCookies as $cookieEntity) {
                    if (empty($requestCookies) || !empty($requestCookies) && !array_key_exists($cookieEntity->getCookieId(), $requestCookies)) {
                        $response->headers->setCookie(new Cookie($cookieEntity->getCookieId(), $cookieEntity->getDefaultValue(), time() + (86400 * 30), '/', null, false, false));
                    }
                }
            }
        }
    }
}
