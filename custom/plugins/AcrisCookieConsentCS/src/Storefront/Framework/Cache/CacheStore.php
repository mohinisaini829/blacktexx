<?php declare(strict_types=1);

namespace Acris\CookieConsent\Storefront\Framework\Cache;

use Shopware\Core\Framework\Adapter\Cache\Http\HttpCacheKeyGenerator;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\HttpCache\StoreInterface;

class CacheStore extends \Shopware\Core\Framework\Adapter\Cache\Http\CacheStore
{
    private StoreInterface $parent;

    public function __construct(
        StoreInterface $parent
    ) {
        $this->parent = $parent;
    }

    /**
     * @return Response|null
     */
    public function lookup(Request $request): ?Response
    {
        // We don't want to load the page from the cache if the request cookie don't has a cache cookie but the cookies are already accepted
        if($request->cookies->has(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE) === false
            && ($request->cookies->has('acris_cookie_acc') === true || $request->cookies->has('cookie-preference') === true)) {
            return null;
        }

        return $this->parent->lookup($request);
    }

    /**
     *
     * We set the cookie which is set to the response also to the request header because we want,
     * that the cache key for the http cache should be immediately created by the new hash,
     * not by the old hash coming from the request
     * @return string
     */
    public function write(Request $request, Response $response): string
    {
        /** @var Cookie $cookie */
        foreach ($response->headers->getCookies() as $cookie) {
            if($cookie->getName() === HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE && !empty($cookie->getValue()) && $cookie->getValue() !== 'deleted') {
                $request->cookies->set(HttpCacheKeyGenerator::CONTEXT_CACHE_COOKIE, $cookie->getValue());
                break;
            }
        }

        return $this->parent->write($request, $response);
    }

    public function invalidate(Request $request): void
    {
        $this->parent->invalidate($request);
    }

    /**
     * Cleanups storage.
     */
    public function cleanup(): void
    {
        $this->parent->cleanup();
    }

    /**
     * Tries to lock the cache for a given Request, without blocking.
     *
     * @return bool|string true if the lock is acquired, the path to the current lock otherwise
     */
    public function lock(Request $request): bool|string
    {
        return $this->parent->lock($request);
    }

    /**
     * Releases the lock for the given Request.
     *
     * @return bool False if the lock file does not exist or cannot be unlocked, true otherwise
     */
    public function unlock(Request $request): bool
    {
        return $this->parent->unlock($request);
    }

    /**
     * Returns whether or not a lock exists.
     *
     * @return bool true if lock exists, false otherwise
     */
    public function isLocked(Request $request): bool
    {
        return $this->parent->isLocked($request);
    }

    /**
     * @return bool
     */
    public function purge(string $url): bool
    {
        return $this->parent->purge($url);
    }
}
