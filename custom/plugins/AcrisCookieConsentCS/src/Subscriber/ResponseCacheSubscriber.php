<?php declare(strict_types=1);

namespace Acris\CookieConsent\Subscriber;

use Shopware\Core\Checkout\Customer\CustomerEntity;
use Shopware\Core\Framework\Adapter\Cache\Http\HttpCacheKeyGenerator;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\Context\SalesChannelContextService;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\Framework\Adapter\Cache\Http\CacheResponseSubscriber;
use Shopware\Storefront\Framework\Routing\MaintenanceModeResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ResponseCacheSubscriber implements EventSubscriberInterface
{
    public const CACHE_HASH_EXTENSION = 'acris_cache_hash';

    public CONST CASH_HASH_COOKIE_LIFETIME = 86400 * 30;

    private bool $httpCacheEnabled;

    private MaintenanceModeResolver $maintenanceModeResolver;

    public function __construct(
        bool $httpCacheEnabled,
        MaintenanceModeResolver $maintenanceModeResolver)
    {
        $this->httpCacheEnabled = $httpCacheEnabled;
        $this->maintenanceModeResolver = $maintenanceModeResolver;
    }

    public static function getSubscribedEvents()
    {
        return [
            KernelEvents::RESPONSE => [
                ['setResponseCache', -2000]
            ]
        ];
    }

    public function addToCacheHash($value, SalesChannelContext $context)
    {
        if($context->hasExtension(self::CACHE_HASH_EXTENSION) === true) {
            /** @var ArrayStruct $cacheHashExtension */
            $cacheHashExtension = $context->getExtension(self::CACHE_HASH_EXTENSION);
        } else {
            $cacheHashExtension = new ArrayStruct();
        }

        $context->addExtension(self::CACHE_HASH_EXTENSION, $this->getCacheHashExtension($value, $cacheHashExtension));
    }

    public function setResponseCache(ResponseEvent $event)
    {
        if (!$this->httpCacheEnabled) {
            return;
        }

        $response = $event->getResponse();

        $request = $event->getRequest();

        if ($this->maintenanceModeResolver->isMaintenanceRequest($request)) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context instanceof SalesChannelContext) {
            return;
        }

        if($context->hasExtension(self::CACHE_HASH_EXTENSION) !== true) {
            return;
        }

        /** @var ArrayStruct $acrisCacheHashExtension */
        $acrisCacheHashExtension = $context->getExtension(self::CACHE_HASH_EXTENSION);
        $acrisCacheHash = $this->generateCacheHashFromExtension($acrisCacheHashExtension);
        $cacheHash = $this->buildCacheHash($context, $acrisCacheHash, $this->getCurrencyIdChanging($request));

        $response->headers->setCookie(new Cookie(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE, $cacheHash, time() + self::CASH_HASH_COOKIE_LIFETIME));
    }

    public function generateCacheHash($value)
    {
        return $this->generateCacheHashFromExtension($this->getCacheHashExtension($value));
    }

    private function getCacheHashExtension($value, ?ArrayStruct $cacheHashExtension = null): ArrayStruct
    {
        if($cacheHashExtension === null) {
            $cacheHashExtension = new ArrayStruct();
        }
        $encodedValue = md5(json_encode($value));
        $cacheHashExtension->set($encodedValue, $encodedValue);
        return $cacheHashExtension;
    }

    private function generateCacheHashFromExtension(ArrayStruct $cacheHashExtension): string
    {
        return md5(json_encode($cacheHashExtension->all()));
    }

    private function buildCacheHash(SalesChannelContext $context, $acrisCacheHash, ?string $currencyId): string
    {
        if(empty($currencyId) == true) {
            $currencyId = $context->getCurrency()->getId();
        }
        return md5(json_encode([
            $context->getRuleIds(),
            $context->getContext()->getVersionId(),
            $currencyId,
            $context->getCustomer() ? 'logged-in' : 'not-logged-in',
            $acrisCacheHash
        ]));
    }

    private function getCurrencyIdChanging(Request $request): ?string
    {
        $route = $request->attributes->get('_route');
        if ($route === 'frontend.checkout.configure') {
            $currencyId = $request->get(SalesChannelContextService::CURRENCY_ID);

            if (!$currencyId) {
                return null;
            }
            return $currencyId;
        }
        return null;
    }
}
