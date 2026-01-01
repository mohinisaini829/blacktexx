<?php declare(strict_types=1);

namespace StudioSolid\AdvancedSliderElements\Core\Content\Cms\ScheduledTask;

use DateTime;
use DateTimeZone;
use Psr\Log\LoggerInterface;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationCollection;
use Shopware\Core\Content\Category\Aggregate\CategoryTranslation\CategoryTranslationEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotCollection;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\ContainsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\OrFilter;
use Shopware\Core\Framework\MessageQueue\ScheduledTask\ScheduledTaskHandler;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler(handles: UpdateScheduledSlidesTask::class)]
final class UpdateScheduledSlidesTaskHandler extends ScheduledTaskHandler
{
    public function __construct(
        protected EntityRepository $scheduledTaskRepository,
        protected readonly LoggerInterface $exceptionLogger,
        private readonly EntityRepository $cmsSlotRepository,
        private readonly EntityRepository $cmsSlotTranslationRepository,
        private readonly EntityRepository $categoryTranslationRepository
    )
    {
        parent::__construct($scheduledTaskRepository);
    }

    public function run(): void
    {
        $context = Context::createDefaultContext();
        $cmsSlots = $this->getCmsSlots($context);

        if (!$cmsSlots->count()) {
            return;
        }

        $this->updateCmsSlotConfig($cmsSlots, $context);
        $this->updateCategorySlotConfig($cmsSlots, $context);
    }

    private function getCmsSlots(Context $context): CmsSlotCollection
    {
        $context = Context::createDefaultContext();
        $criteria = (new Criteria())
            ->addFilter(new EqualsFilter('type', 'solid-ase-content-slider'))
            ->addAssociation('translations');

        /**
         * @var CmsSlotCollection $cmsSlots
         */
        $cmsSlots = $this->cmsSlotRepository->search($criteria, $context)->getEntities();

        return $cmsSlots;
    }

    private function getCategoryTranslationsBySlotIds(Context $context, array $cmsSlotIds): CategoryTranslationCollection
    {
        $criteria = (new Criteria())
            ->addFilter(new OrFilter(array_map(static function ($cmsSlotId) {
                return new ContainsFilter('slotConfig', $cmsSlotId);
            }, $cmsSlotIds)));

        /**
         * @var CategoryTranslationCollection $categoryTranslations
         */
        $categoryTranslations = $this->categoryTranslationRepository->search($criteria, $context)->getEntities();

        return $categoryTranslations;
    }

    private function updateCmsSlotConfig(CmsSlotCollection $cmsSlots, Context $context): void
    {
        /**
         * @var CmsSlotEntity $cmsSlot
         */
        foreach ($cmsSlots as $cmsSlot) {
            $cmsSlotTranslations = $cmsSlot->getTranslations();

            if (!$cmsSlotTranslations) {
                continue;
            }

            foreach ($cmsSlotTranslations as $cmsSlotTranslation) {
                $config = $cmsSlotTranslation->getConfig();
                $slidesConfig = $config['slides']['value'];
                $updatedSlidesConfig = $this->updateSlidesConfigScheduledSlides($slidesConfig);
                $config['slides']['value'] = $updatedSlidesConfig;

                $this->cmsSlotTranslationRepository->update([
                    [
                        'cmsSlotId' => $cmsSlot->getId(),
                        'cmsSlotVersionId' => $cmsSlot->getVersionId(),
                        'languageId' => $cmsSlotTranslation->getLanguageId(),
                        'config' => $config,
                    ],
                ], $context);
            }
        }
    }

    private function updateCategorySlotConfig(CmsSlotCollection $cmsSlots, Context $context): void {
        $cmsSlotIds = array_values($cmsSlots->map(static function (CmsSlotEntity $cmsSlot) {
            return $cmsSlot->getId();
        }));

        $categoryTranslations = $this->getCategoryTranslationsBySlotIds($context, $cmsSlotIds);

        /**
         * @var CategoryTranslationEntity $category
         */
        foreach ($categoryTranslations as $categoryTranslation) {
            $slotConfig = $categoryTranslation->getSlotConfig();

            foreach ($slotConfig as $cmsSlotId => $configOverride) {
                if (!in_array($cmsSlotId, $cmsSlotIds)) {
                    continue;
                }

                $slidesConfig = $configOverride['slides']['value'];
                $updatedSlidesConfig = $this->updateSlidesConfigScheduledSlides($slidesConfig);
                $configOverride['slides']['value'] = $updatedSlidesConfig;
                $slotConfig[$cmsSlotId] = $configOverride;
            }

            $this->categoryTranslationRepository->update([
                [
                    'categoryId' => $categoryTranslation->getCategoryId(),
                    'categoryVersionId' => $categoryTranslation->getVersionId(),
                    'languageId' => $categoryTranslation->getLanguageId(),
                    'slotConfig' => $slotConfig,
                ],
            ], $context);
        }
    }

    private function updateSlidesConfigScheduledSlides(array $slidesConfig): array
    {
        foreach ($slidesConfig as &$slideConfig) {
            if (!isset($slideConfig['publishingType'])) {
                continue;
            }

            $publishingType = $slideConfig['publishingType'];

            if ($publishingType !== 'scheduled') {
                continue;
            }

            $utcDateTime = new DateTime('now', new DateTimeZone('UTC'));
            $scheduledPublishingDateTime = $slideConfig['scheduledPublishingDateTime'];
            $scheduledPublishingUtcDateTime = null;
            $scheduledUnpublishingDateTime = $slideConfig['scheduledUnpublishingDateTime'];
            $scheduledUnpublishingUtcDateTime = null;

            if ($scheduledPublishingDateTime) {
                $scheduledPublishingUtcDateTime = new DateTime($scheduledPublishingDateTime, new DateTimeZone('UTC'));
            }

            if ($scheduledUnpublishingDateTime) {
                $scheduledUnpublishingUtcDateTime = new DateTime($scheduledUnpublishingDateTime, new DateTimeZone('UTC'));
            }

            if ($scheduledPublishingUtcDateTime && $scheduledPublishingUtcDateTime <= $utcDateTime) {
                $slideConfig['active'] = true;
            }

            if ($scheduledUnpublishingUtcDateTime && $scheduledUnpublishingUtcDateTime <= $utcDateTime) {
                $slideConfig['active'] = false;
            }
        }

        // Unset reference to avoid stuck references
        unset($slideConfig);

        return $slidesConfig;
    }
}
