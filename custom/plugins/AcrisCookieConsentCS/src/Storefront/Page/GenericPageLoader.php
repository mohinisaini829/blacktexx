<?php declare(strict_types=1);

namespace Acris\CookieConsent\Storefront\Page;

use Acris\CookieConsent\Components\CookiesAcceptService;
use Acris\CookieConsent\Components\CookieService;
use Shopware\Core\Content\Cms\CmsPageEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\GenericPageLoaderInterface;
use Shopware\Storefront\Page\Page;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use DOMDocument;

class GenericPageLoader extends \Shopware\Storefront\Page\GenericPageLoader
{
    const COOKIE_STRING_SEPERATOR = "_||_";

    /**
     * @var GenericPageLoaderInterface
     */
    private $genericPageLoader;
    /**
     * @var ContainerInterface
     */
    private $container;
    /**
     * @var CookieService
     */
    private $cookieService;
    /**
     * @var CookiesAcceptService
     */
    private $cookiesAcceptService;
    /**
     * @var SystemConfigService
     */
    private $systemConfigService;
    /**
     * @var EntityRepository
     */
    private $cmsPageRepository;

    public function __construct(
        GenericPageLoaderInterface $genericPageLoader,
        ContainerInterface $container,
        CookieService $cookieService,
        CookiesAcceptService $cookiesAcceptService,
        SystemConfigService $systemConfigService,
        EntityRepository $cmsPageRepository
    ) {
        $this->genericPageLoader = $genericPageLoader;
        $this->container = $container;
        $this->cookieService = $cookieService;
        $this->cookiesAcceptService = $cookiesAcceptService;
        $this->systemConfigService = $systemConfigService;
        $this->cmsPageRepository = $cmsPageRepository;
    }

    public function load(Request $request, SalesChannelContext $salesChannelContext): Page
    {
        if ($request->isXmlHttpRequest()) {
            return $this->genericPageLoader->load($request, $salesChannelContext);
        }

        $salesChannelId = $salesChannelContext->getSalesChannel()->getId();

        $session = $request->getSession();
        $hasAccepted = $this->cookiesAcceptService->getCookiesAccepted($session, $request);

        $knownCookieIds = $this->cookieService->getAllKnownCookieIds($salesChannelContext->getContext(), $salesChannelId);
        $cookieGroups = $this->cookieService->getAvailableCookieGroups($salesChannelContext->getContext(), $salesChannelId);

        $deniedCookieGroups = $session->get('acrisCookieGroupsDenied', []);
        $deniedCookies = $session->get('acrisCookiesDenied', []);

        $cookieGroups = $this->convertAndGetCookieGroups($session, $cookieGroups, $deniedCookieGroups, $deniedCookies, $hasAccepted, $salesChannelId);

        if(!$hasAccepted) {
            $acceptedCookies = $this->replaceRegexSigns($this->convertCookieGroupsToAcceptedString($cookieGroups, true));
        } else {
            $acceptedCookies = $this->replaceRegexSigns($this->convertCookieGroupsToAcceptedString($cookieGroups, false, $deniedCookieGroups, $deniedCookies));
        }
        $acceptedCookies = $this->addExpectedCookies($acceptedCookies, $this->cookieService::EXPECTED_COOKIES);

        $page = $this->genericPageLoader->load($request, $salesChannelContext);

        $page->addExtension('acrisCookieConsent', new ArrayEntity([
            'hasAccepted' => $hasAccepted,
            'cookieGroups' => $cookieGroups,
            'acceptedCookies' => $acceptedCookies,
            'knownCookies' => $this->replaceRegexSigns(implode(self::COOKIE_STRING_SEPERATOR, $knownCookieIds)),
            'cookieStringSeparator' => self::COOKIE_STRING_SEPERATOR,
            'footerPages' => $this->loadFooterPageData($salesChannelContext)
        ]));

        return $page;
    }

    /**
     * @param array $cookieGroups
     * @param bool $onlyDefault
     * @param array $deniedCookieGroups
     * @param array $deniedCookies
     * @return string
     */
    private function convertCookieGroupsToAcceptedString($cookieGroups, $onlyDefault = true, $deniedCookieGroups = [], $deniedCookies = [])
    {
        $cookieArray = [];
        foreach ($cookieGroups as $key => $cookieGroup) {
            foreach ($cookieGroup['cookies'] as $cookie) {
                if(!in_array($cookieGroup['id'], $deniedCookieGroups) && !in_array($cookie['id'], $deniedCookies)) {
                    if(($onlyDefault === true && ($cookieGroup['isDefault'] || $cookie['isDefault'])) || ($onlyDefault === false && $cookie['active'])) {
                        $cookieArray[] = $cookie['cookieId'];
                    }
                }
            }
        }

        return implode(self::COOKIE_STRING_SEPERATOR, $cookieArray);
    }

    /**
     * @param Session $session
     * @param array $cookieGroups
     * @param array $deniedCookieGroups
     * @param array $deniedCookies
     * @param boolean $hasAccepted
     * @return array
     */
    private function convertAndGetCookieGroups($session, $cookieGroups, $deniedCookieGroups, $deniedCookies, $hasAccepted, $salesChannelId): array
    {
        if(!$hasAccepted) $deniedCookieGroups = $this->cookiesAcceptService->checkDenyNonFunctionalCookiesForDefault($session, $cookieGroups, $deniedCookieGroups, $salesChannelId);
        if($deniedCookieGroups || $deniedCookies) {
            foreach ($cookieGroups as $key => $cookieGroup) {
                $groupDenied = in_array($cookieGroup['id'], $deniedCookieGroups);
                $cookieGroups[$key]['denied'] = $groupDenied;
                foreach ($cookieGroup['cookies'] as $j => $cookie) {
                    if(($deniedCookies !== null && in_array($cookie['id'], $deniedCookies) === true) || $groupDenied === true) {
                        $cookieGroups[$key]['cookies'][$j]['denied'] = true;
                    }
                }
            }
        }

        foreach ($cookieGroups as $key => $cookieGroup) {
            foreach ($cookieGroup['cookies'] as $j => $cookie) {
                if($cookie['translated']['script']) {
                    if(array_key_exists('denied', $cookie)) {
                        $cookieGroups[$key]['cookies'][$j]['translated']['script'] = $this->convertCookieScript($cookie['translated']['script'], $cookie['cookieId'], $cookie['denied']);
                    }else{
                        $cookieGroups[$key]['cookies'][$j]['translated']['script'] = $this->convertCookieScript($cookie['translated']['script'], $cookie['cookieId']);
                    }
                }
            }
        }

        return $cookieGroups;
    }

    /**
     * @param string $script
     * @param string $cookieId
     */
    private function convertCookieScript($script, $cookieId, $cookieStateDenied = false) {
        if(strpos(trim($script), "<script") !== false) {
            return $this->changeScript($script, $cookieId, $cookieStateDenied);
        } else {
            if($cookieStateDenied) {
                return '<script type="text/plain" data-acriscookie="true" data-acriscookieid="' . $cookieId . '">' . $script . '</script>';
            }else{
                return '<script type="text/javascript" data-acriscookie="true" data-acriscookieid="' . $cookieId . '">' . $script . '</script>';
            }
        }
    }

    private function replaceRegexSigns($string)
    {
        return str_replace('\\', '\\\\', $string);
    }

    private function addExpectedCookies($acceptedCookiesString, array $expectedCookies): string
    {
        $expectedCookiesString = implode(self::COOKIE_STRING_SEPERATOR, $expectedCookies);

        if($acceptedCookiesString) {
            return $acceptedCookiesString . self::COOKIE_STRING_SEPERATOR . $expectedCookiesString;
        } else {
            return $expectedCookiesString;
        }
    }

    private function loadFooterPageData(SalesChannelContext $salesChannelContext)
    {
        return [
            'footerPageOne' => $this->loadPageDataById($this->systemConfigService->get('AcrisCookieConsentCS.config.privacyLinkFooterOne', $salesChannelContext->getSalesChannel()->getId()), $salesChannelContext->getContext()),
            'footerPageTwo' => $this->loadPageDataById($this->systemConfigService->get('AcrisCookieConsentCS.config.privacyLinkFooterTwo', $salesChannelContext->getSalesChannel()->getId()), $salesChannelContext->getContext()),
            'footerPageThree' => $this->loadPageDataById($this->systemConfigService->get('AcrisCookieConsentCS.config.privacyLinkFooterThree', $salesChannelContext->getSalesChannel()->getId()), $salesChannelContext->getContext())
        ];
    }

    private function loadPageDataById($cmsPageId, Context $context): ?CmsPageEntity
    {
        if(empty($cmsPageId) === true) {
            return null;
        }

        return $this->cmsPageRepository->search(new Criteria([$cmsPageId]), $context)->first();
    }

    private function changeScript($html, $cookieId, $cookieStateDenied = false) {
        $dom = new DOMDocument;
        $dom->loadHTML($html);
        $scripts = $dom->getElementsByTagName('script');
        $noScripts = $dom->getElementsByTagName('noscript');

        if (isset($scripts[0])) {
            foreach ($scripts as $script) {
                if($cookieStateDenied) {
                    $script->setAttribute('type', 'text/plain');
                }else{
                    $script->setAttribute('type', 'text/javascript');
                }
                $script->setAttribute('data-acriscookie', 'true');
                $script->setAttribute('data-acriscookieid', $cookieId);
                $dom->saveHTML($script);
            }

            foreach ($noScripts as $noScript) {
                $dom->saveHTML($noScript);
            }

            $head = $dom->getElementsByTagName('head')->item(0);

            $headContent = '';
            foreach ($head->childNodes as $node) {
                $headContent .= $dom->saveHTML($node);
            }

            return $headContent;
        } else {
            return '';
        }
    }
}
