<?php
namespace SantaFeTexTheme\Subscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Struct\ArrayStruct;

class NavigationSubscriber implements EventSubscriberInterface
{
    
    private EntityRepository $categoryRepository;

    public function __construct(EntityRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            NavigationPageLoadedEvent::class => 'onNavigationPageLoaded',
        ];
    }

    public function onNavigationPageLoaded(NavigationPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getSalesChannelContext()->getContext();
        $categoryId = $page->getNavigationId();

        $criteria = new Criteria([$categoryId]);
        $criteria->addAssociation('children.media'); // Load children with media for the template

        $result = $this->categoryRepository->search($criteria, $context);
        $category = $result->get($categoryId);

        if ($category && $category->getChildren()->count() > 0) {
            $page->addExtension('subcategories', new ArrayStruct($category->getChildren()->getElements()));
        }
    }
}


