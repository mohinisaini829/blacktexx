<?php declare(strict_types=1);

namespace Acris\CookieConsent\Components;

use Acris\CookieConsent\Custom\CookieEntity;
use Acris\CookieConsent\Subscriber\ResponseCacheSubscriber;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class CookiesAcceptService
{
    const COOKIE_STRING_SEPERATOR = "_||_";
    const COOKIES_SEPERATOR = "_cc_";

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly ResponseCacheSubscriber $responseCacheSubscriber,
        private readonly SystemConfigService $configService
    ) { }

    public function getCookiesAccepted(SessionInterface $session, Request $request)
    {
        $cookiesAccepted = $session->get('acrisCookieAccepted');
        if($cookiesAccepted) return true;
        $deniedCookiesFromCookie = $request->cookies->get('acris_cookie_acc');

        $allowCookie = $request->cookies->get('cookie-preference');

        $salesChannelId = $session->has(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID) ? $session->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_ID) : null;

        $forceConsent = $this->configService->get('AcrisCookieConsentCS.config.forceConsent', $salesChannelId);

        if ($forceConsent || (!$deniedCookiesFromCookie && !$allowCookie)) {
            return false;
        }

        if($deniedCookiesFromCookie) {
            $strpos = strrpos($deniedCookiesFromCookie,self::COOKIES_SEPERATOR);
            if($strpos === false) $strpos = 0;
            $deniedCookieGroups = substr($deniedCookiesFromCookie,0,$strpos);
            $this->splitDeniedFromCookieAndSafeToSession($session, $deniedCookieGroups, 'acrisCookieGroupsDenied');

            $deniedCookies = substr($deniedCookiesFromCookie,strrpos($deniedCookiesFromCookie,self::COOKIES_SEPERATOR) + strlen(self::COOKIES_SEPERATOR));
            $this->splitDeniedFromCookieAndSafeToSession($session, $deniedCookies, 'acrisCookiesDenied');
        }

        $session->set('acrisCookieAccepted', true);
        return true;
    }

    /**
     * @param Session $session
     * @param string $denied
     * @param string $sessionSaveString
     */
    protected function splitDeniedFromCookieAndSafeToSession($session, $denied, $sessionSaveString)
    {
        if(!$denied) return;
        $deniedArray = explode(self::COOKIE_STRING_SEPERATOR, $denied);
        if(!empty($deniedArray)) {
            $deniedCookiesFormattedArray = [];
            foreach ($deniedArray as $groupId) {
                $deniedCookiesFormattedArray[$groupId] = $groupId;
            }
            $session->set($sessionSaveString, $deniedCookiesFormattedArray);
        }
    }

    /**
     * @param Response $response
     * @param string $deniedGroups
     * @param string $deniedCookies
     */
    public function setAllowCookies(Request $request, Response $response, $deniedGroups = "", $deniedCookies = "")
    {
        if(!$deniedGroups && !$deniedCookies) {
            $denied = "1";
        } else {
            $denied = $deniedGroups . self::COOKIES_SEPERATOR . $deniedCookies;
        }

        $firstActivated = $request->cookies->get("acris_cookie_first_activated");
        if($firstActivated) {
            $response->headers->setCookie(new Cookie("acris_cookie_acc", $denied, time() + (86400 * 30), '/', null, false, false));
            $response->headers->setCookie(new Cookie("cookie-preference", "1", time() + (86400 * 30), '/', null, false, false));

            $request->cookies->set("acris_cookie_acc", $denied);
            $request->cookies->set("cookie-preference", "1");
        }
    }

    /**
     * @param Session $session
     * @param int $groupId
     * @param Context $context
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function removeCookiesDenyRememberByGroup(Session $session, $groupId, Context $context)
    {
        $deniedCookies = $session->get('acrisCookiesDenied');
        if(!empty($deniedCookies)) {
            /** @var EntityRepository $cookieGroupRepository */
            $cookieGroupRepository = $this->container->get('acris_cookie.repository');
            /** @var EntitySearchResult $cookieIdsByGroupId */
            $cookieIdsByGroupId = $cookieGroupRepository->search((new Criteria())->addFilter(new EqualsFilter('cookieGroupId', $groupId)), $context);

            $iterator = $cookieIdsByGroupId->getIterator();
            while ($iterator->valid()) {
                /** @var CookieEntity $cookie */
                $cookie = $iterator->current();
                unset($deniedCookies[$cookie->getId()]);
                $iterator->next();
            }
            $session->set('acrisCookiesDenied', $deniedCookies);
        }
    }

    public function getDeniedCookiesFromSession(Session $session, $type): string
    {
        $deniedCookies = $session->get($type);
        if($deniedCookies) {
            sort($deniedCookies);
            return implode(self::COOKIE_STRING_SEPERATOR, $deniedCookies);
        }
        return "";
    }

    public function updateCookieFromSessionData(SessionInterface $session, Request $request): void
    {
        $deniedGroups = $this->getDeniedCookiesFromSession($session, 'acrisCookieGroupsDenied');
        $deniedCookies = $this->getDeniedCookiesFromSession($session, 'acrisCookiesDenied');
        $acrisCookieAccepted = $session->get('acrisCookieAccepted');
        if(!$acrisCookieAccepted && !$deniedGroups && !$deniedCookies) {
            $denied = "";
        } else {
            $denied = $acrisCookieAccepted . "_" . $deniedGroups . self::COOKIES_SEPERATOR . $deniedCookies;
        }
        $this->updateCacheCookie($request, $denied);
    }

    public function updateCacheCookie(Request $request, $denied = ""): void
    {
        if(empty($denied)) {
            // fallback to get from request cookies if session is not filled yet
            $acrisRememberCookie = $request->cookies->get('acris_cookie_acc', '');
            $permissionCookie = $request->cookies->get('cookie-preference', '');
            if($permissionCookie || $acrisRememberCookie) {
                $denied = $permissionCookie . "_" . $acrisRememberCookie;
            }
        }
        if(empty($denied)) {
            return;
        }

        $context = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if (!$context instanceof SalesChannelContext) {
            return;
        }

        $this->responseCacheSubscriber->addToCacheHash($denied, $context);
    }

    public function checkDenyNonFunctionalCookiesForDefault(Session $session, array $cookieGroups, array $deniedCookieGroups, string $salesChannelId): array
    {
        if(!$deniedCookieGroups) {
            if(!$this->configService->get('AcrisCookieConsentCS.config.defaultActive', $salesChannelId)) {
                foreach ($cookieGroups as $key => $cookieGroup) {
                    if($cookieGroup['isDefault'] !== true) $deniedCookieGroups[$cookieGroup['id']] = $cookieGroup['id'];
                }
                $session->set('acrisCookieGroupsDenied', $deniedCookieGroups);
            }
        }
        return $deniedCookieGroups;
    }
}
