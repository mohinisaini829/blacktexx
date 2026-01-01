<?php declare(strict_types=1);

namespace Acris\CookieConsent\Components;

use Acris\CookieConsent\AcrisCookieConsentCS as AcrisCookieConsent;
use Acris\CookieConsent\Custom\CookieEntity;
use Acris\CookieConsent\Custom\CookieGroupEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityCollection;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\NotFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Symfony\Component\DependencyInjection\ContainerInterface;

class CookieService
{
    const EXPECTED_COOKIES = [
        'sf_redirect',
        'googtrans',
        'language',
        'PHPSESSID',
        'acrisImportExportCookie'
    ];

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly EntityRepository $cookieRepository,
        private readonly EntityRepository $cookieGroupRepository,
        private readonly AcrisCookieConsent $acrisCookieConsent,
        private readonly SystemConfigService $systemConfigService
    ) { }

    /**
     * @param Context $context
     * @param string $salesChannelId
     * @return array
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function getAllKnownCookieIds(Context $context, string $salesChannelId): array
    {
        /** @var EntityCollection $cookies */
        $cookies = $this->cookieRepository->search((new Criteria())->addAssociation('salesChannels'), $context)->getEntities();
        $cookieIds = [];
        if(!$cookies) return [];
        /** @var CookieEntity $cookie */
        foreach ($cookies->getElements() as $cookie) {
            if($cookie->isDefault() !== true && $this->isCookieAvailableForSalesChannels($salesChannelId, $cookie->getSalesChannels()) === false) {
                continue;
            }
            $cookieIds[] = $cookie->getCookieId();
        }
        return $cookieIds;
    }

    /**
     * @param Context $context
     * @param string $salesChannelId
     * @return array
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function getAvailableCookieGroups(Context $context, string $salesChannelId): array
    {
        /** @var EntitySearchResult $cookieGroups */
        $cookieGroups = $this->cookieGroupRepository->search((new Criteria())->addAssociation('cookies')->addAssociation('cookies.salesChannels')->addSorting(new FieldSorting('isDefault', FieldSorting::DESCENDING)), $context);

        $cookieGroupArray = [];
        /** @var CookieGroupEntity $cookieGroup */
        foreach ($cookieGroups->getElements() as $cookieGroup) {
            $cookies = $cookieGroup->getCookies();
            if(!empty($cookies)) {
                $cookiesArray = [];
                foreach ($cookies->getElements() as $cookie) {
                    if($cookie->isActive() === true) {
                        // check if cookie is allowed for shop
                        if(!$cookie->isDefault()) {
                            if($this->isCookieAvailableForSalesChannels($salesChannelId, $cookie->getSalesChannels()) === false) continue;
                        }
                        $cookiesArray[$cookie->getId()] = $cookie->getVars();
                    }
                }
                if(!empty($cookiesArray)) {
                    $cookieGroupArray[$cookieGroup->getId()] = $cookieGroup->getVars();
                    $cookieGroupArray[$cookieGroup->getId()]['cookies'] = $cookiesArray;
                }
            }
        }

        return $cookieGroupArray;
    }

    /**
     * @param string $salesChannelId
     * @param SalesChannelCollection|null $salesChannelCollection
     * @return bool
     */
    protected function isCookieAvailableForSalesChannels(string $salesChannelId, ?SalesChannelCollection $salesChannelCollection): bool
    {
        if($salesChannelCollection && $salesChannelCollection->count()) {
            foreach ($salesChannelCollection->getElements() as $salesChannelEntity) {
                if($salesChannelEntity->getId() === $salesChannelId) {
                    return true;
                }
            }
            return false;
        }
        return true;
    }

    public function getAllCookies(SalesChannelContext $salesChannelContext): EntityCollection
    {
        return $this->cookieRepository->search((new Criteria())->addAssociation('salesChannels'), $salesChannelContext->getContext())->getEntities();
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @return array
     * @throws \Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException
     */
    public function getNotFunctionalCookieGroupIds(SalesChannelContext $salesChannelContext): array
    {
        $notFunctionalGroups = $this->cookieGroupRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('isDefault', false)), $salesChannelContext->getContext())->getIds();
        $notFunctionalGroupArray = [];
        foreach ($notFunctionalGroups as $notFunctionalGroupId) {
            $notFunctionalGroupArray[$notFunctionalGroupId] = $notFunctionalGroupId;
        }
        return $notFunctionalGroupArray;
    }

    /**
     * @param SalesChannelContext $salesChannelContext
     * @param string $cookieName
     * @param false $force
     * @param null $availableCookies
     * @param array $additionalCookieData
     * @param false $isShopwareCookie
     */
    public function insertCookieIfNotKnown(SalesChannelContext $salesChannelContext, string $cookieName, $force = false, $availableCookies = null, $additionalCookieData = [], $isShopwareCookie = false): void
    {
        if($isShopwareCookie === false && !$this->systemConfigService->get('AcrisCookieConsentCS.config.autoDetectionActive', $salesChannelContext->getSalesChannel()->getId())) {
            return;
        }

        if(!$cookieName) return;

        if(in_array($cookieName, self::EXPECTED_COOKIES)) return;

        if(!$force) {
            if($availableCookies === null) {
                $availableCookies = $this->getAllCookies($salesChannelContext);
            }
            if($this->isCookieKnownForSalesChannel($cookieName, $availableCookies, $salesChannelContext->getSalesChannel()->getId(), $salesChannelContext->getContext()) === true) {
                return;
            }
        }

        $knownCookies = parse_ini_file($this->acrisCookieConsent->getPath() . "/Components/Resources/optionalKnownCookies.ini", true);

        foreach ($knownCookies as $cookieId => $knownCookie) {
            if($knownCookie['groupIdentification'] && $cookieName === $cookieId || @preg_match("#^(" . $cookieId . ")$#", $cookieName)) {
                $groupId = $this->findGroupIdByIdentification($salesChannelContext, $knownCookie['groupIdentification']);
                if(!$groupId && !empty($additionalCookieData) && array_key_exists('cookieGroupId', $additionalCookieData) === true && $additionalCookieData['cookieGroupId']) {
                    $groupId = $additionalCookieData['cookieGroupId'];
                }

                if(!$groupId) continue;
                $data = ['cookieId'=>$cookieId,'translations' => [
                    'en-GB' => [
                        'title' => $knownCookie['title-en'],
                        'description' => $knownCookie['description-en'],
                    ],'de-DE' => [
                        'title' => $knownCookie['title-de'],
                        'description' => $knownCookie['description-de'],
                    ],[
                        'title' => $knownCookie['title-en'],
                        'description' => $knownCookie['description-en'],
                        'languageId' => Defaults::LANGUAGE_SYSTEM
                    ]
                    ],'provider'=>$knownCookie['provider'],'unknown'=>false,'active'=>true,'cookieGroupId'=>$groupId, 'salesChannels' => [0 => ['id' => $salesChannelContext->getSalesChannel()->getId()]]];
                if(array_key_exists('defaultValue', $additionalCookieData) && $additionalCookieData['defaultValue']) {
                    $data['defaultValue'] = $additionalCookieData['defaultValue'];
                }

                if(array_key_exists("googleCookieConsentMode", $knownCookie)) {
                    $googleCookieConsentMode = [];

                    if(str_contains($knownCookie["googleCookieConsentMode"], "|")){
                        $googleCookieConsentModes = explode("|", $knownCookie["googleCookieConsentMode"]);

                        foreach($googleCookieConsentModes as $cookieConsentMode){
                            $googleCookieConsentMode[] = $cookieConsentMode;
                        }
                    } else {
                        $googleCookieConsentMode[] = $knownCookie["googleCookieConsentMode"];
                    }

                    $data["googleCookieConsentMode"] = $googleCookieConsentMode;
                }

                $this->cookieRepository->create([$data], $salesChannelContext->getContext());
                return;
            }
        }
        $cookieData = array_merge(['cookieId'=>$cookieName,'unknown'=>true,'active'=>false, 'salesChannels' => [0 => ['id' => $salesChannelContext->getSalesChannel()->getId()]]], $additionalCookieData);
        $this->cookieRepository->create([$cookieData], $salesChannelContext->getContext());
    }

    public function findGroupIdByIdentification(SalesChannelContext $salesChannelContext, string $identification): string
    {
        $cookieGroups = $this->cookieGroupRepository->searchIds((new Criteria())->addFilter(new EqualsFilter('identification', $identification))->setLimit(1), $salesChannelContext->getContext())->getIds();
        empty($cookieGroups) ? $cookieGroupId = "" : $cookieGroupId = $cookieGroups[0];
        return $cookieGroupId;
    }

    public function isCookieKnownForSalesChannel(string $cookieName, EntityCollection $availableCookies, string $currentSalesChannelId, Context $context): bool
    {
        /** @var CookieEntity $availableCookie */
        foreach ($availableCookies->getElements() as $availableCookie) {
            try {
                if($cookieName === $availableCookie->getCookieId() || @preg_match("#^(" . $availableCookie->getCookieId() . ")$#", $cookieName)) {
                    if($availableCookie->getSalesChannels()) {
                        foreach ($availableCookie->getSalesChannels()->getElements() as $salesChannel) {
                            if($currentSalesChannelId === $salesChannel->getId()) {
                                return true;
                            }
                        }
                    }
                    $this->addSalesChannelToCookie($availableCookie, $currentSalesChannelId, $context);
                    return true;
                }
            } catch (\Throwable $e) { }
        }
        return false;
    }

    public function getDefaultCookies(String $salesChannelId, Context $context): EntityCollection {
        $criteria = new Criteria();
        $criteria->addAssociation('salesChannels')
                 ->addFilter(new EqualsFilter('active', true))
                 ->addFilter(new NotFilter(NotFilter::CONNECTION_AND,
                            [new EqualsFilter('defaultValue', null)]))
                 ->addFilter(new OrFilter([new EqualsFilter('salesChannels.id', null), new EqualsFilter('salesChannels.id', $salesChannelId)]))
                 ->addFilter(new EqualsFilter('cookieGroup.isDefault', true));
        return $this->cookieRepository->search($criteria, $context)->getEntities();
    }

    private function addSalesChannelToCookie(CookieEntity $availableCookie, string $currentSalesChannelId, Context $context): void
    {
        $salesChannelsArray = [];
        if($availableCookie->getSalesChannels()) {
            foreach ($availableCookie->getSalesChannels() as $salesChannel) {
                $salesChannelsArray[] = ['id'=> $salesChannel->getId()];
            }
        }
        $salesChannelsArray[] = ['id'=> $currentSalesChannelId];

        $this->cookieRepository->upsert([['id'=>$availableCookie->getId(), 'salesChannels' => $salesChannelsArray]], $context);
    }
}
