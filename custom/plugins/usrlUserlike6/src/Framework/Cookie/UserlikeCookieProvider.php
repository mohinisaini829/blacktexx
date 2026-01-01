<?php declare(strict_types=1);

namespace UserlikeUG\usrlUserlike6\Framework\Cookie;

use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;

class UserlikeCookieProvider implements CookieProviderInterface {

    private $originalService;

    public function __construct(CookieProviderInterface $service)
    {
        $this->originalService = $service;
    }

    private const userlikeCookie = [
        'snippet_name' => 'userlike.cookie_name',
        'snippet_description' => 'userlike.cookie_description',
        'cookie' => '_usrlk_accepted',
        'value'=> '1',
        'expiration' => '365'
    ];

    public function getCookieGroups(): array
    {
        return array_merge(
            $this->originalService->getCookieGroups(),
            [
                self::userlikeCookie,
            ]
        );
    }
}