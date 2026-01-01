<?php declare(strict_types=1);

namespace Acris\CookieConsent\Components;

use Acris\CookieConsent\AcrisCookieConsentCS as AcrisCookieConsent;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\Snippet\Aggregate\SnippetSet\SnippetSetEntity;
use Shopware\Storefront\Framework\Cookie\CookieProviderInterface;
use Shopware\Core\System\Snippet\SnippetService;

class RegisteredCookiesService
{
    public const DEFAULT_SNIPPET_LANGUAGE_ISO = 'en-GB';
    public const EXTENSION_PROVIDER = 'Shopware Plugin';

    private $snippetSets;

    public function __construct(
        private readonly CookieProviderInterface $cookieProvider,
        private readonly CookieService $cookieService,
        private readonly SnippetService $snippetService,
        private readonly EntityRepository $snippetSetRepository,
        private readonly EntityRepository $cookieGroupRepository
    )
    {
        $this->snippetSets = [];
    }

    public function getKnownShopCookies(SalesChannelContext $salesChannelContext, EntityCollection $availableCookies)
    {
        $functionalCookieGroupId = null;
        foreach ($this->cookieProvider->getCookieGroups() as $cookieGroup) {
            if(array_key_exists('entries', $cookieGroup) === false) {
                // checks if single cookie
                if(array_key_exists('cookie', $cookieGroup) === true) {
                    $orgCookieGroup = $cookieGroup;
                    $cookieGroup = [
                        'entries' => [$cookieGroup]
                    ];
                    if(array_key_exists('snippet_name', $orgCookieGroup) === true) {
                        $cookieGroup['snippet_name'] = $orgCookieGroup['snippet_name'];
                    }
                } else {
                    continue;
                }
            }
            $isRequired = array_key_exists('isRequired', $cookieGroup) && $cookieGroup['isRequired'];
            $cookieGroupId = null;
            if($isRequired && !$functionalCookieGroupId) {
                $functionalCookieGroupId = $this->cookieService->findGroupIdByIdentification($salesChannelContext, AcrisCookieConsent::DEFAULT_FUNCTIONAL_GROUP_IDENTIFICATION);
            } else {
                if(array_key_exists('snippet_name', $cookieGroup) === true && $cookieGroup['snippet_name']) {
                    $cookieGroupResult = $this->cookieGroupRepository->search((new Criteria())->addFilter(new EqualsFilter('identification', $cookieGroup['snippet_name'])), $salesChannelContext->getContext());
                    if($cookieGroupResult->getTotal() == 0) {
                        $cookieGroupId = Uuid::randomHex();
                        $cookieGroupText = $this->getTranslationOfCookieInfos($cookieGroup, $salesChannelContext, $cookieGroup['snippet_name']);
                        $cookieGroupData = [
                            'id' => $cookieGroupId,
                            'identification' => $cookieGroup['snippet_name']
                        ];
                        if(!empty($cookieGroupText)) {
                            $cookieGroupData = array_merge($cookieGroupData, $cookieGroupText);
                        }
                        $this->cookieGroupRepository->create([$cookieGroupData], $salesChannelContext->getContext());
                    } else {
                        $cookieGroupId = $cookieGroupResult->first()->getId();
                    }
                }
            }

            foreach ($cookieGroup['entries'] as $cookie) {
                if(is_array($cookie) === false || array_key_exists('cookie', $cookie) === false || !$cookie['cookie']) {
                    continue;
                }

                if(array_key_exists('value', $cookie)){
                    if($this->getShopwarePluginCookieDontInsert($cookie['cookie'], $cookie['value'], $salesChannelContext) === true) {
                        continue;
                    }
                }else{
                    if($this->getShopwarePluginCookieDontInsert($cookie['cookie'], "0", $salesChannelContext) === true) {
                        continue;
                    }
                }

                if($this->cookieService->isCookieKnownForSalesChannel($cookie['cookie'], $availableCookies, $salesChannelContext->getSalesChannel()->getId(), $salesChannelContext->getContext()) === true) {
                    continue;
                }

                $additionalCookieData = [];
                if($isRequired === true && $functionalCookieGroupId) {
                    $additionalCookieData['cookieGroupId'] = $functionalCookieGroupId;
                } elseif($cookieGroupId) {
                    $additionalCookieData['cookieGroupId'] = $cookieGroupId;
                }
                $cookieText = $this->getTranslationOfCookieInfos($cookie, $salesChannelContext, $cookie['cookie']);
                if(!empty($cookieText)) {
                    $additionalCookieData = array_merge($additionalCookieData, $cookieText);
                }
                if(array_key_exists('value', $cookie) && $cookie['value']) {
                    $additionalCookieData['defaultValue'] = (string) $cookie['value'];
                }
                $additionalCookieData['unknown'] = false;
                $additionalCookieData['active'] = true;
                $additionalCookieData['provider'] = self::EXTENSION_PROVIDER;
                $additionalCookieData['fromExtension'] = true;

                $this->cookieService->insertCookieIfNotKnown($salesChannelContext, $cookie['cookie'], false, $availableCookies, $additionalCookieData, true);
            }
        }
    }

    private function getTranslationOfCookieInfos(array $snippetData, SalesChannelContext $salesChannelContext, string $fallbackValue = '')
    {
        $snippetName = "";
        if(array_key_exists('snippet_name', $snippetData) === true && $snippetData['snippet_name']) {
            $snippetName = $snippetData['snippet_name'];
        }
        $snippetDescription = "";
        if(array_key_exists('snippet_description', $snippetData) === true && $snippetData['snippet_description']) {
            $snippetDescription = $snippetData['snippet_description'];
        }

        if(!$snippetName) {
            return [
                'translations' => [
                    0 => [
                        'title' => 'No translation for snippets found. Identifier: ' . $fallbackValue,
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                ]
            ];
        }

        $snippetResult = $this->getTranslation($snippetName, 'title', $salesChannelContext);
        if($snippetDescription) {
            $snippetResult = $this->getTranslation($snippetDescription, 'description', $salesChannelContext, $snippetResult);
        }
        return $this->addDefaultTranslation($snippetResult);
    }

    public function getTranslation($snippet, $type, SalesChannelContext $salesChannelContext, $translationMerge = [])
    {
        $snippetsResult = $this->snippetService->getList(1, 25, $salesChannelContext->getContext(), ['translationKey' => [$snippet]], []);

        if(empty($snippetsResult)) {
            $translationMerge[$type] = $snippet;
            return $translationMerge;
        }

        if(empty($this->snippetSets)) {
            $this->snippetSets = $this->snippetSetRepository->search(new Criteria(), $salesChannelContext->getContext());
        }

        if(array_key_exists('data', $snippetsResult) === false || array_key_exists($snippet, $snippetsResult['data']) === false) {
            $translationMerge[$type] = $snippet;
            return $translationMerge;
        }

        if(array_key_exists('translations', $translationMerge) === false) {
            $translationMerge['translations'] = [];
        }

        foreach ($snippetsResult['data'][$snippet] as $snippetData) {
            if(array_key_exists('setId', $snippetData) === false || array_key_exists('value', $snippetData) === false || !$snippetData['value']) {
                continue;
            }
            $isoCode = $this->getIsoCodeBySetId($snippetData['setId']);
            if(!$isoCode) {
                continue;
            }
            $translationMerge['translations'][$isoCode][$type] = $snippetData['value'];
        }
        if(empty($translationMerge['translations'])) {
            $translationMerge[$type] = $snippet;
        }
        return $translationMerge;
    }

    private function getIsoCodeBySetId($setId): string
    {
        /** @var SnippetSetEntity $snippetSet */
        foreach ($this->snippetSets->getElements() as $snippetSet) {
            if($snippetSet->getId() === $setId) {
                return $snippetSet->getIso();
            }
        }
        return "";
    }

    private function getShopwarePluginCookieDontInsert(?string $cookieName, $cookieValue,SalesChannelContext $salesChannelContext): bool
    {
        switch ($cookieName) {
            case 'google-analytics-enabled':
                if($cookieValue == "1"){
                    return false;
                }else{
                    return !$salesChannelContext->getSalesChannel()->getAnalytics() || !$salesChannelContext->getSalesChannel()->getAnalytics()->isActive() || !$salesChannelContext->getSalesChannel()->getAnalytics()->getTrackingId();
                }
        }
        return false;
    }

    private function addDefaultTranslation($snippetResult)
    {
        if(is_array($snippetResult) === true && array_key_exists('translations', $snippetResult) === true && is_array($snippetResult['translations']) === true) {
            if(array_key_exists(self::DEFAULT_SNIPPET_LANGUAGE_ISO, $snippetResult['translations']) === true && is_array($snippetResult['translations'][self::DEFAULT_SNIPPET_LANGUAGE_ISO]) === true) {
                $defaultTranslation = $snippetResult['translations'][self::DEFAULT_SNIPPET_LANGUAGE_ISO];
            } else {
                $defaultTranslation = end($snippetResult['translations']);
            }
            $defaultTranslation['languageId'] = Defaults::LANGUAGE_SYSTEM;
            $snippetResult['translations'][] = $defaultTranslation;
            return $snippetResult;
        }
        return $snippetResult;
    }
}
