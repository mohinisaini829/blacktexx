<?php

declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Core\Content\Media\Subscriber;

use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationEntity;
use Shopware\Core\Content\Media\Event\UnusedMediaSearchEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MediaSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly EntityRepository $cmsSlotRepository
    )
    {
    }

    public static function getSubscribedEvents()
    {
        return [
            UnusedMediaSearchEvent::class => 'onUnusedMediaSearch',
        ];
    }

    public function onUnusedMediaSearch(UnusedMediaSearchEvent $event): void
    {
        $context = Context::createDefaultContext();
        $contentSliderMediaIds = $this->getContentSliderMediaIds($context);
        $event->markAsUsed($contentSliderMediaIds);
    }

    private function getContentSliderMediaIds(Context $context): array
    {
        $mediaIds = [];

        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('type', 'solid-ase-content-slider'))
            ->addAssociation('translations');

        /**
         * @var CmsSlotCollection $cmsSlots
         */
        $cmsSlots = $this->cmsSlotRepository->search($criteria, $context)->getEntities();

        /**
         * @var CmsSlotEntity $cmsSlot
         */
        foreach ($cmsSlots as $cmsSlot) {
            /**
             * @var CmsSlotTranslationEntity $cmsSlotTranslation
             */
            foreach ($cmsSlot->getTranslations() as $cmsSlotTranslation) {
                $config = $cmsSlotTranslation->getConfig();

                foreach ($config['slides']['value'] as $slide) {
                    if (isset($slide['backgroundMedia'])) {
                        $mediaIds[] = $slide['backgroundMedia'];
                    }
                }
            }
        }

        return $mediaIds;
    }
}
