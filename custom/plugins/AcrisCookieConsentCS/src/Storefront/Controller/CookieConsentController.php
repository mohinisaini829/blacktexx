<?php declare(strict_types=1);

namespace Acris\CookieConsent\Storefront\Controller;

use Acris\CookieConsent\Components\CookiesAcceptService;
use Acris\CookieConsent\Components\CookieService;
use Acris\CookieConsent\Core\Content\CookieConsent\Exception\CookieNameNotFoundException;
use Shopware\Core\PlatformRequest;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Storefront\Controller\StorefrontController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @Route(defaults={"_routeScope" = {"storefront"}})
 */
#[Route(defaults: ['_routeScope' => ['storefront']])]
class CookieConsentController extends StorefrontController
{
    const COOKIE_STRING_SEPERATOR = "_||_";

    /**
     * @var CookiesAcceptService
     */
    private $cookiesAcceptService;
    /**
     * @var CookieService
     */
    private $cookieService;

    public function __construct(CookiesAcceptService $cookiesAcceptService, CookieService $cookieService)
    {
        $this->cookiesAcceptService = $cookiesAcceptService;
        $this->cookieService = $cookieService;
    }

    /**
     * @Route("/cookie-consent/remember", name="frontend.cookieConsent.remember", options={"seo"="false"}, methods={"GET"}, defaults={"XmlHttpRequest": true})
     */
    #[Route("/cookie-consent/remember", name: "frontend.cookieConsent.remember", options: ["seo" => "false"], methods: ["GET"], defaults: ["XmlHttpRequest" => true])]
    public function rememberCookie(Request $request): Response
    {
        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);
        $cookieName = $request->query->get('c');

        if (!$cookieName) {
            throw new CookieNameNotFoundException();
        }

        $this->cookieService->insertCookieIfNotKnown($salesChannelContext, $cookieName);
        return new JsonResponse(['success' => true]);
    }

    /**
     * @Route("/cookie-consent/accept", name="frontend.cookieConsent.accept", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    #[Route("/cookie-consent/accept", name: "frontend.cookieConsent.accept", options: ["seo" => "false"], methods: ["POST"], defaults: ["XmlHttpRequest" => true])]
    public function accept(Request $request, SalesChannelContext $salesChannelContext): Response
    {
        $session = $request->getSession();
        $hasAccepted = (bool) $session->get('acrisCookieAccepted');
        if(!$hasAccepted) {
            $cookieGroups = $this->cookieService->getAvailableCookieGroups($salesChannelContext->getContext(), $salesChannelContext->getSalesChannelId());
            $deniedCookieGroups = $session->get('acrisCookieGroupsDenied', []);
            $this->cookiesAcceptService->checkDenyNonFunctionalCookiesForDefault($session, $cookieGroups, $deniedCookieGroups, $salesChannelContext->getSalesChannelId());
        }

        $accept = $request->request->get('accept');

        $session->set("acrisCookieAccepted", $accept);

        $response = new JsonResponse(['success' => true]);

        $deniedGroups = $this->cookiesAcceptService->getDeniedCookiesFromSession($session, 'acrisCookieGroupsDenied');
        $deniedCookies = $this->cookiesAcceptService->getDeniedCookiesFromSession($session, 'acrisCookiesDenied');
        $this->cookiesAcceptService->setAllowCookies($request, $response, $deniedGroups, $deniedCookies);

        return $response;
    }

    /**
     * @Route("/cookie-consent/allow-cookie-group", name="frontend.cookieConsent.allowCookieGroup", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    #[Route("/cookie-consent/allow-cookie-group", name: "frontend.cookieConsent.allowCookieGroup", options: ["seo" => "false"], methods: ["POST"], defaults: ["XmlHttpRequest" => true])]
    public function allowCookieGroup(Request $request): Response
    {
        $session = $request->getSession();

        if($request->request->get('accept')) {
            $session->set("acrisCookieAccepted", true);
        }

        $groupId = $request->request->get('groupId');
        $cookieId = $request->request->get('cookieId');
        $allow = (bool) $request->request->get('allow');

        if(empty($groupId) && empty($cookieId)) return new JsonResponse(['success' => false]);

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        if(!empty($groupId)) {
            $this->cookiesAcceptService->removeCookiesDenyRememberByGroup($session, $groupId, $salesChannelContext->getContext());
            $this->updateDeniedSessionString($session, 'acrisCookieGroupsDenied', $allow, $groupId);
        }

        if(!empty($cookieId)) {
            $this->updateDeniedSessionString($session, 'acrisCookiesDenied', $allow, $cookieId);
        }

        $response = new JsonResponse(['success' => true]);

        $deniedGroups = $this->cookiesAcceptService->getDeniedCookiesFromSession($session, 'acrisCookieGroupsDenied');
        $deniedCookies = $this->cookiesAcceptService->getDeniedCookiesFromSession($session, 'acrisCookiesDenied');
        $this->cookiesAcceptService->setAllowCookies($request, $response, $deniedGroups, $deniedCookies);

        return $response;
    }

    /**
     * @Route("/cookie-consent/allow-only-functional", name="frontend.cookieConsent.allowOnlyFunctional", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    #[Route("/cookie-consent/allow-only-functional", name: "frontend.cookieConsent.allowOnlyFunctional", options: ["seo" => "false"], methods: ["POST"], defaults: ["XmlHttpRequest" => true])]
    public function allowOnlyFunctional(Request $request): Response
    {
        $session = $request->getSession();

        /** @var SalesChannelContext $salesChannelContext */
        $salesChannelContext = $request->attributes->get(PlatformRequest::ATTRIBUTE_SALES_CHANNEL_CONTEXT_OBJECT);

        $deniedGroups = $this->cookieService->getNotFunctionalCookieGroupIds($salesChannelContext);

        $session->set('acrisCookieGroupsDenied', $deniedGroups);
        $session->remove('acrisCookiesDenied');

        $response = new JsonResponse(['success' => true]);

        sort($deniedGroups);
        $deniedGroupsString = implode(self::COOKIE_STRING_SEPERATOR, $deniedGroups);
        $this->cookiesAcceptService->setAllowCookies($request, $response, $deniedGroupsString);

        $session->set("acrisCookieAccepted", true);

        return $response;
    }

    /**
     * @Route("/cookie-consent/allow-all", name="frontend.cookieConsent.allowAll", options={"seo"="false"}, methods={"POST"}, defaults={"XmlHttpRequest": true})
     */
    #[Route("/cookie-consent/allow-all", name: "frontend.cookieConsent.allowAll", options: ["seo" => "false"], methods: ["POST"], defaults: ["XmlHttpRequest" => true])]
    public function allowAll(Request $request)
    {
        $session = $request->getSession();

        $session->remove('acrisCookieGroupsDenied');
        $session->remove('acrisCookiesDenied');

        $response = new JsonResponse(['success' => true]);

        $this->cookiesAcceptService->setAllowCookies($request, $response);

        $session->set("acrisCookieAccepted", true);

        return $response;
    }

    private function updateDeniedSessionString(SessionInterface $session, string $sessionSaveString, bool $allow, string $id): void
    {
        $denied = $session->get($sessionSaveString, []);
        if($allow) {
            if(in_array($id, $denied)) {
                unset($denied[$id]);
                $session->set($sessionSaveString, $denied);
            }
        } else {
            if(!in_array($id, $denied)) {
                $denied[$id] = $id;
                $session->set($sessionSaveString, $denied);
            }
        }
    }
}
