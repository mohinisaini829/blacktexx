<?php declare(strict_types=1);

namespace Swag\BasicExample\Migration\Test;

use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use Shopware\Core\Content\Cms\Aggregate\CmsSlot\CmsSlotEntity;
use Shopware\Core\Content\Cms\Aggregate\CmsSlotTranslation\CmsSlotTranslationEntity;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\Test\TestCaseBase\KernelTestBehaviour;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\Content\Cms\DataResolver\FieldConfig;
use StudioSolid\AdvancedSliderElements\Migration\Migration1688378836UpdateContentSliderDuplicateIds;

class Migration1688378836UpdateContentSliderDuplicateIdsTest extends TestCase
{
    use KernelTestBehaviour;

    private Connection $connection;

    private EntityRepository $cmsPageRepository;

    private EntityRepository $cmsSectionRepository;

    private EntityRepository $cmsBlockRepository;

    private EntityRepository $cmsSlotRepository;

    private EntityRepository $categoryRepository;

    protected function setUp(): void
    {
        $this->connection = $this->getContainer()->get(Connection::class);
        $this->cmsPageRepository = $this->getContainer()->get('cms_page.repository');
        $this->cmsSectionRepository = $this->getContainer()->get('cms_section.repository');
        $this->cmsBlockRepository = $this->getContainer()->get('cms_block.repository');
        $this->cmsSlotRepository = $this->getContainer()->get('cms_slot.repository');
        $this->categoryRepository = $this->getContainer()->get('category.repository');

        parent::setUp();
    }

    public function test(): void
    {
        $context = Context::createDefaultContext();

        $slideId = Uuid::randomHex();

        $this->createTestData(
            $context,
            $slideId
        );

        $this->runMigration();
        $this->validateUniqueSlideIds($context);
    }

    private function createTestData(
        Context $context,
        string $slideId
    ): void {
        $cmsPageIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $cmsSectionIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $cmsBlockIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $cmsSlotIds = [
            Uuid::randomHex(),
            Uuid::randomHex(),
            Uuid::randomHex(),
            Uuid::randomHex(),
        ];

        $sliderConfig = [
            'slides' => [
                'source' => FieldConfig::SOURCE_STATIC,
                'value' => [
                    [
                        'id' => $slideId,
                        'link' => 'value2',
                        'name' => 'Slide 1',
                        'text' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                        'active' => true,
                        'headline' => 'Lorem ipsum dolor sit amet',
                        'linkTitle' => '',
                        'buttonLink' => '#',
                        'buttonLabel' => 'Lorem ipsum',
                        'buttonTitle' => '',
                        'contentType' => 'default',
                        'customContent' => '',
                        'smallHeadline' => 'Lorem ipsum',
                        'backgroundColor' => '',
                        'backgroundMedia' => null,
                        'linkTargetBlank' => false,
                        'buttonTargetBlank' => false,
                        'backgroundPosition' => 'center',
                        'backgroundAnimation' => 'move',
                        'backgroundSizingMode' => 'cover'
                    ],
                    [
                        'id' => $slideId,
                        'link' => 'value2',
                        'name' => 'Slide 1',
                        'text' => 'Lorem ipsum dolor sit amet, consetetur sadipscing elitr, sed diam nonumy eirmod tempor.',
                        'active' => true,
                        'headline' => 'Lorem ipsum dolor sit amet',
                        'linkTitle' => '',
                        'buttonLink' => '#',
                        'buttonLabel' => 'Lorem ipsum',
                        'buttonTitle' => '',
                        'contentType' => 'default',
                        'customContent' => '',
                        'smallHeadline' => 'Lorem ipsum',
                        'backgroundColor' => '',
                        'backgroundMedia' => null,
                        'linkTargetBlank' => false,
                        'buttonTargetBlank' => false,
                        'backgroundPosition' => 'center',
                        'backgroundAnimation' => 'move',
                        'backgroundSizingMode' => 'cover'
                    ],
                ]
            ],
            'settings' => [
                'source' => FieldConfig::SOURCE_STATIC,
                'value' => [
                    'navSize' => 'medium',
                    'navColor' => '#00000099',
                    'customCss' => '',
                    'textColor' => '',
                    'navVariant' => 'dots-fill',
                    'sizingMode' => 'responsive-min-height',
                    'textWeight' => '',
                    'buttonColor' => '',
                    'navPosition' => 'horizontal-bottom-center',
                    'buttonVariant' => 'primary',
                    'controlsColor' => '',
                    'headlineColor' => '',
                    'layoutVariant' => 'overlay-center',
                    'headlineWeight' => '',
                    'textSizeMobile' => '',
                    'textSizeTablet' => '',
                    'controlsVariant' => 'round',
                    'minHeightMobile' => '300px',
                    'minHeightTablet' => '500px',
                    'textSizeDesktop' => '',
                    'contentAnimation' => 'none',
                    'controlsPosition' => 'horizontal-inside-center-edges',
                    'minHeightDesktop' => '800px',
                    'buttonLabelWeight' => '',
                    'headlineSizeMobile' => '',
                    'headlineSizeTablet' => '',
                    'smallHeadlineColor' => '',
                    'controlsIconVariant' => 'arrow',
                    'headlineSizeDesktop' => '',
                    'minAspectRatioWidth' => '2',
                    'smallHeadlineWeight' => '',
                    'minAspectRatioHeight' => '1',
                    'buttonLabelSizeMobile' => '',
                    'buttonLabelSizeTablet' => '',
                    'buttonLabelSizeDesktop' => '',
                    'contentBackgroundColor' => '',
                    'textMarginBottomMobile' => '',
                    'textMarginBottomTablet' => '',
                    'controlsCustomImageNext' => null,
                    'smallHeadlineSizeMobile' => '',
                    'smallHeadlineSizeTablet' => '',
                    'textMarginBottomDesktop' => '',
                    'smallHeadlineSizeDesktop' => '',
                    'headlineMarginBottomMobile' => '',
                    'headlineMarginBottomTablet' => '',
                    'controlsCustomImagePrevious' => null,
                    'headlineMarginBottomDesktop' => '',
                    'smallHeadlineMarginBottomMobile' => '',
                    'smallHeadlineMarginBottomTablet' => '',
                    'smallHeadlineMarginBottomDesktop' => ''
                ]
            ],
            'sliderSettings' => [
                'source' => FieldConfig::SOURCE_STATIC,
                'value' => [
                    'nav' => true,
                    'loop' => true,
                    'items' => '1',
                    'speed' => '500',
                    'gutter' => '0',
                    'rewind' => false,
                    'slideBy' => '1',
                    'autoplay' => true,
                    'controls' => true,
                    'animation' => 'slider-horizontal-ease-in-out-sine',
                    'itemsMode' => 'responsive-automatic',
                    'mouseDrag' => true,
                    'startIndex' => '0',
                    'itemsMobile' => '1',
                    'itemsTablet' => '1',
                    'itemsDesktop' => '1',
                    'slideByMobile' => '1',
                    'slideByTablet' => '1',
                    'slideByDesktop' => '1',
                    'autoplayTimeout' => '4000',
                    'autoplayDirection' => 'forward',
                    'autoplayHoverPause' => false
                ]
            ]
        ];

        foreach ($cmsPageIds as $pageId) {
            $this->cmsPageRepository->create([[
                'id' => $pageId,
                'type' => 'landingpage'
            ]], $context);
        }

        foreach ($cmsSectionIds as $index => $sectionId) {
            $this->cmsSectionRepository->create([[
                'id' => $sectionId,
                'pageId' => $cmsPageIds[$index],
                'position' => 0,
                'type' => 'default'
            ]], $context);
        }

        foreach ($cmsBlockIds as $index => $blockId) {
            $this->cmsBlockRepository->create([[
                'id' => $blockId,
                'sectionId' => $cmsSectionIds[$index],
                'position' => 0,
                'sectionPosition' => 'main',
                'type' => 'solid-ase-content-slider'
            ]], $context);
        }

        foreach ($cmsSlotIds as $index => $slotId) {
            $this->cmsSlotRepository->create([[
                'id' => $slotId,
                'blockId' => $cmsBlockIds[$index % count($cmsBlockIds)],
                'type' => 'solid-ase-content-slider',
                'slot' => 'slider',
                'config' => $sliderConfig
            ]], $context);
        }

        /*
        $this->categoryRepository->create([[
            'id' => Uuid::randomHex(),
            'cmsPageId' => $cmsPageIds[0],
            'level' => 1,
            'name' => 'Category with slider layout',
            'slotConfig' => [
                $cmsSlotIds[0] => $sliderConfig
            ]
        ]], $context);
        */
    }

    private function runMigration(): void
    {
        $migration = new Migration1688378836UpdateContentSliderDuplicateIds();
        $migration->update($this->connection);
    }

    private function validateUniqueSlideIds(Context $context): void
    {
        $criteria = new Criteria();
        $criteria
            ->addFilter(new EqualsFilter('type', 'solid-ase-content-slider'))
            ->addAssociation('translations');
        $cmsSlotSearchResult = $this->cmsSlotRepository->search($criteria, $context);

        $cmsSlots = $cmsSlotSearchResult->getElements();

        /**
          * @var CmsSlotEntity
          */
        foreach ($cmsSlots as $cmsSlot) {
            $cmsSlotTranslations = $cmsSlot->getTranslations();

            /**
               * @var CmsSlotTranslationEntity
               */
            foreach ($cmsSlotTranslations as $cmsSlotTranslation) {
                $config = $cmsSlotTranslation->getConfig();
                $slideIds = array_map(function($slide) {
                    return $slide['id'];
                }, $config['slides']['value']);
                $uniqueSlideIds = array_unique($slideIds);

                static::assertEquals($slideIds, $uniqueSlideIds, 'Slide IDs are not unique');
            }
        }
    }
}
