<?php declare(strict_types=1);

namespace Acris\CookieConsent\Subscriber;

use Acris\CookieConsent\Components\CookiesAcceptService;
use Acris\CookieConsent\Components\CookieService;
use Acris\CookieConsent\Components\RegisteredCookiesService;
use Acris\CookieConsent\Custom\CookieEntity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseSubscriber implements EventSubscriberInterface
{
    const EXPECTED_COOKIES = [
        'sf_redirect',
        'googtrans',
        'language',
        'PHPSESSID'
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly CookieService $cookieService,
        private readonly CookiesAcceptService $cookiesAcceptService,
        private readonly SystemConfigService $systemConfigService,
        private readonly RegisteredCookiesService $registeredCookiesService
    )
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['checkResponseCookies', 90000],
                ['setResponseCache', 1500]
            ]
        ];
    }

    public function checkResponseCookies(ResponseEvent $responseEvent): void
    {
        $request = $responseEvent->getRequest();

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$salesChannelContext instanceof SalesChannelContext) return;
        if(!$this->systemConfigService->get('AcrisCookieConsentCS.config.active', $salesChannelContext->getSalesChannel()->getId())) return;

        $response = $responseEvent->getResponse();
        $session = $request->getSession();
        $cookiesAccepted = $this->cookiesAcceptService->getCookiesAccepted($session, $request);
        $deniedCookieGroups = $session->get("acrisCookieGroupsDenied", []);
        $deniedCookies = $session->get("acrisCookiesDenied", []);

        $availableCookies = $this->cookieService->getAllCookies($salesChannelContext);

        $this->registeredCookiesService->getKnownShopCookies($salesChannelContext, $availableCookies);

        foreach($request->cookies->all() as $cookieName => $cookieValue) {
            if(in_array($cookieName, self::EXPECTED_COOKIES)) continue;

            /** @var CookieEntity $availableCookie */
            foreach ($availableCookies->getElements() as $availableCookie) {
                // check if cookie is known
                try {
                    if($cookieName === $availableCookie->getCookieId() || @preg_match("#^(" . $availableCookie->getCookieId() . ")$#", $cookieName)) {
                        if(!$availableCookie->isDefault() && ($availableCookie->getCookieGroup() === null || !$availableCookie->getCookieGroup()->isDefault())) {
                            // check if cookies are accepted by user
                            if(!$cookiesAccepted || !$availableCookie->isActive()) {
                                $this->clearCookie($response, $cookieName);
                            } elseif(!empty($deniedCookieGroups) || !empty($deniedCookies)) {
                                // check if cookie group is denied by user
                                if(in_array($availableCookie->getCookieGroupId(), $deniedCookieGroups)) {
                                    $this->clearCookie($response, $cookieName);
                                    break;
                                }
                                // check if cookie is denied by user
                                if(in_array($availableCookie->getId(), $deniedCookies)) {
                                    $this->clearCookie($response, $cookieName);
                                    break;
                                }
                            }
                        }
                        continue 2;
                    }
                } catch (\Throwable $e) { }
            }

            // is new cookie which is not known - so clear it and add it to the database
            $this->clearCookie($response, $cookieName);
        }

        $this->insertResponseCookieIfNotKnown($response, $salesChannelContext, $availableCookies);
    }

    public function clearCookie($response, $cookieName): void
    {
        $cookieName = (string) $cookieName;
        if(!empty($cookieName)) {
            unset($_COOKIE[$cookieName]);
            if($response->headers) {
                $response->headers->clearCookie($cookieName);
            }
        }
    }

    public function setResponseCache(ResponseEvent $event)
    {
        $request = $event->getRequest();
        if(empty($request->attributes) === true) {
            return;
        }

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        if (!$salesChannelContext instanceof SalesChannelContext) return;
        if(!$this->systemConfigService->get('AcrisCookieConsentCS.config.active', $salesChannelContext->getSalesChannel()->getId())) return;

        try {
            $session = $event->getRequest()->getSession();
            $this->cookiesAcceptService->updateCookieFromSessionData($session, $request);
            $session->save();
        } catch (\Throwable $e) { }
    }

    private function insertResponseCookieIfNotKnown(Response $response, SalesChannelContext $salesChannelContext, EntityCollection $availableCookies): void
    {
        if($response->headers) {
            foreach ($response->headers->getCookies() as $cookie) {
                if($cookie->getValue() !== NULL) {
                    $this->cookieService->insertCookieIfNotKnown($salesChannelContext, $cookie->getName(), false, $availableCookies);
                }
            }
        }
    }
}
