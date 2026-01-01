<?php
declare(strict_types=1);

namespace Sas\BlogModule\Storefront\Framework\Seo\SeoUrlRoute;

use Sas\BlogModule\Content\Blog\BlogCategorySeoUrlRoute;
use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Sas\BlogModule\Content\Blog\BlogSeoUrlRoute;
use Sas\BlogModule\Content\Blog\Events\BlogCategoryIndexerEvent;
use Sas\BlogModule\Content\Blog\Events\BlogIndexerEvent;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryCollection;
use Shopware\Core\Content\Seo\SeoUrlUpdater;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\RangeFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SeoUrlUpdateListener implements EventSubscriberInterface
{
    /**
     * @internal
     *
     * @param EntityRepository<BlogEntriesCollection>  $blogRepository
     * @param EntityRepository<BlogCategoryCollection> $blogCategoryRepository
     */
    public function __construct(
        private readonly SeoUrlUpdater $seoUrlUpdater,
        private readonly EntityRepository $blogRepository,
        private readonly EntityRepository $blogCategoryRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            BlogIndexerEvent::class => 'updateBlogUrls',
            BlogCategoryIndexerEvent::class => 'updateBlogCategoryUrls',
            'sales_channel.written' => 'onCreateNewSalesChannel',
        ];
    }

    public function updateBlogUrls(BlogIndexerEvent $event): void
    {
        if (\count($event->getIds()) === 0) {
            return;
        }

        $this->seoUrlUpdater->update(BlogSeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    public function updateBlogCategoryUrls(BlogCategoryIndexerEvent $event): void
    {
        if (\count($event->getIds()) === 0) {
            return;
        }

        $this->seoUrlUpdater->update(BlogCategorySeoUrlRoute::ROUTE_NAME, $event->getIds());
    }

    public function onCreateNewSalesChannel(EntityWrittenEvent $event): void
    {
        if (\count($event->getIds()) === 0) {
            return;
        }

        /** @var list<string> $blogArticlesIds */
        $blogArticlesIds = $this->getBlogArticlesIds($event->getContext());
        if (\count($blogArticlesIds) > 0) {
            $this->seoUrlUpdater->update(BlogSeoUrlRoute::ROUTE_NAME, $blogArticlesIds);
        }

        /** @var list<string> $blogCategoryIds */
        $blogCategoryIds = $this->getBlogCategoryIds($event->getContext());
        if (\count($blogCategoryIds) > 0) {
            $this->seoUrlUpdater->update(BlogCategorySeoUrlRoute::ROUTE_NAME, $blogCategoryIds);
        }
    }

    /**
     * @return list<string>|list<array<string, string>>
     */
    private function getBlogArticlesIds(Context $context): array
    {
        $criteria = new Criteria();

        $dateTime = new \DateTime();
        $criteria->addFilter(
            new EqualsFilter('active', true),
            new RangeFilter('publishedAt', [RangeFilter::LTE => $dateTime->format(\DATE_ATOM)])
        );

        return $this->blogRepository->searchIds($criteria, $context)->getIds();
    }

    /**
     * @return list<string>|list<array<string, string>>
     */
    private function getBlogCategoryIds(Context $context): array
    {
        return $this->blogCategoryRepository->searchIds(new Criteria(), $context)->getIds();
    }
}
