<?php declare(strict_types=1);

namespace Acris\CookieConsent\Core\Content\CookieConsent\Exception;

use Shopware\Core\Framework\ShopwareHttpException;
use Symfony\Component\HttpFoundation\Response;

class CookieNameNotFoundException extends ShopwareHttpException
{
    public function __construct()
    {
        parent::__construct(
            'Cookie name is empty.'
        );
    }

    public function getStatusCode(): int
    {
        return Response::HTTP_NOT_FOUND;
    }

    public function getErrorCode(): string
    {
        return 'COOKIE_CONTENT__COOKIE_EMPTY_NAME';
    }
}
