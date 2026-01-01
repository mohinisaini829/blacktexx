<?php declare(strict_types = 1);

namespace HTC\Popup\Service;

use HTC\Popup\Core\Content\Popup\PopupEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsAnyFilter;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\FetchMode;
use Shopware\Storefront\Page\PageLoadedEvent;

/**
 * Class PopupService
 * @package HTC\Popup\Service
 */
class PopupService
{
    /**
     * Const
     */
    const HOMEPAGE_ROUTE = 'home.page';
    /**
     * Const
     */
    const CATEGORY_ROUTE = 'navigation.page';
    /**
     * Const
     */
    const PRODUCTPAGE_ROUTE = 'detail.page';

    /**
     * @var Connection
     */
    private $connection;

    /**
     * @var EntityRepository $popupRepository
     */
    protected $popupRepository;

    /**
     * PopupService constructor.
     * @param Connection $connection
     * @param EntityRepository $popupRepository
     */
    public function __construct(
        Connection $connection,
        EntityRepository $popupRepository
    ) {
        $this->connection = $connection;
        $this->popupRepository = $popupRepository;
    }

    /**
     * @param PageLoadedEvent $event
     * @return PopupEntity|null
     */
    public function getPopupsForLoadedPage(PageLoadedEvent $event): ?PopupEntity
    {
        $route = $event->getRequest()->attributes->get('_route');
        $salesChannelId = $event->getSalesChannelContext()->getSalesChannelId();
        /**
         * Check customer login
         */
        if ($event->getSalesChannelContext()->getCustomer()) {
            $isLoggedIn = true;
            $currentCustomerGroupId = $event->getSalesChannelContext()->getCustomer()->getGroupId();
        } else {
            $isLoggedIn = false;
            $currentCustomerGroupId = null;
        }
        $popupId = $this->getMatchPopupId($route, $currentCustomerGroupId, $isLoggedIn, $salesChannelId);
        $criteria = new Criteria();
        $criteria->addFilter(new EqualsAnyFilter('id', $popupId));
        $criteria->addAssociation('media');
        $result = $this->popupRepository->search($criteria, $event->getContext());

        return $result->first();
    }

    /**
     * @param $route
     * @return int
     */
    private function getCurrentPageId($route)
    {
        if (strpos($route, self::HOMEPAGE_ROUTE)) {
            return PopupEntity::HOMEPAGE_VISIBLE_PAGE;
        } else {
            if (strpos($route, self::CATEGORY_ROUTE)) {
                return PopupEntity::CATEGORYPAGE_VISIBLE_PAGE;
            } else {
                if (strpos($route, self::PRODUCTPAGE_ROUTE)) {
                    return PopupEntity::PRODUCTPAGE_VISIBLE_PAGE;
                } else {
                    return PopupEntity::OTHERPAGE_VISIBLE_PAGE;
                }
            }
        }
    }

    /**
     * @param $route
     * @param $currentCustomerGroupId
     * @param $isLoggedIn
     * @return array|\Doctrine\DBAL\list
     * @throws \Doctrine\DBAL\Exception
     */
    public function getMatchPopupId($route, $currentCustomerGroupId, $isLoggedIn, $salesChannelId)
    {
        $queryBuilder = $this->connection->createQueryBuilder()
            ->select('LOWER(HEX(p.id))')
            ->from('htc_popup', 'p')
            ->leftJoin('p', 'htc_popup_translation', 'pt', 'p.id = pt.htc_popup_id')
            ->where('p.active = 1')
            ->andWhere('pt.stores = :stores')
            ->andWhere("FIND_IN_SET(:currentPageId, p.`visible_on`)")
            ->setParameter('stores', $salesChannelId)
            ->setParameter('currentPageId', $this->getCurrentPageId($route));
        if ($isLoggedIn) {
            $queryBuilder->andWhere(
                "FIND_IN_SET(:currentCustomerGroupId, p.`customer_group_ids`)"
            )->setParameter(
                "currentCustomerGroupId", $currentCustomerGroupId
            );
        } else {
            $queryBuilder->andWhere("p.show_guest = 1");
        }
        $queryBuilder->addOrderBy('p.priority', 'ASC')->setMaxResults(1);
        $ids = $queryBuilder->executeQuery()->fetchFirstColumn();
        if (empty($ids)) {
            return [];
        }

        return $ids;
    }
}
