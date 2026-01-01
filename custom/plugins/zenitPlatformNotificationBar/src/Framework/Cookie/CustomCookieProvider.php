<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Framework\Cookie;

use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class CustomCookieProvider implements CookieProviderInterface
{
    private const singleCookie = [
        'snippet_name' => 'zenit.notificationBar.cookieName',
        'snippet_description' => 'zenit.notificationBar.cookieDescription',
        'cookie' => 'zen-notification-bar',
        'value' => '1',
        'expiration' => '365',
    ];

    public function __construct(private readonly SystemConfigService $systemConfigService, private readonly CookieProviderInterface $originalService)
    {
    }

    public function getCookieGroups(): array
    {
        /* ... we only need to inject a cookie, if the banner is closeable */
        if (!$this->systemConfigService->get('zenitPlatformNotificationBar.config.collapse')) {
            return $this->originalService->getCookieGroups();
        }

        $cookies = $this->originalService->getCookieGroups();
        foreach ($cookies as &$cookie) {
            if (!\is_array($cookie)) {
                continue;
            }

            if (!$this->isComfortFeaturesGroup($cookie)) {
                continue;
            }

            if (!\array_key_exists('entries', $cookie)) {
                continue;
            }

            $cookie['entries'][] = array_merge(
                self::singleCookie
            );
        }

        return $cookies;
    }

    private function isComfortFeaturesGroup(array $cookie): bool
    {
        return \array_key_exists('snippet_name', $cookie) && $cookie['snippet_name'] === 'cookie.groupComfortFeatures';
    }
}
