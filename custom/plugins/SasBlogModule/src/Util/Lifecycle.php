<?php
declare(strict_types=1);

namespace Sas\BlogModule\Util;

use Shopware\Core\Content\Cms\CmsPageCollection;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\SystemConfig\SystemConfigService;

class Lifecycle
{
    /**
     * @param EntityRepository<CmsPageCollection> $cmsPageRepository
     */
    public function __construct(
        private readonly SystemConfigService $systemConfig,
        private readonly EntityRepository $cmsPageRepository
    ) {
    }

    public function install(Context $context): void
    {
        $this->createBlogCmsListingPage($context);
    }

    private function createBlogCmsListingPage(Context $context): void
    {
        $blogListingCmsPageId = Uuid::randomHex();
        $blogDetailCmsPageId = Uuid::randomHex();

        $cmsPage = $this->createCmsPagesData($blogListingCmsPageId, $blogDetailCmsPageId);

        $this->cmsPageRepository->create($cmsPage, $context);
        $this->systemConfig->set('SasBlogModule.config.cmsBlogDetailPage', $blogDetailCmsPageId);
    }

    /**
     * @return array<array<string, mixed>>
     */
    private function createCmsPagesData(string $blogListingCmsPageId, string $blogDetailCmsPageId): array
    {
        $blogListingCmsPageData = $this->createBlogListingCmsPageData($blogListingCmsPageId);
        $blogDetailCmsPageData = $this->createBlogDetailCmsPageData($blogDetailCmsPageId);

        return [
            $blogListingCmsPageData,
            $blogDetailCmsPageData,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createBlogListingCmsPageData(string $blogListingCmsPageId): array
    {
        return [
            'id' => $blogListingCmsPageId,
            'type' => 'page',
            'name' => 'Blog Listing',
            'sections' => [
                [
                    'id' => Uuid::randomHex(),
                    'type' => 'default',
                    'position' => 0,
                    'blocks' => [
                        [
                            'position' => 1,
                            'type' => 'blog-listing',
                            'slots' => [
                                ['type' => 'blog', 'slot' => 'listing'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function createBlogDetailCmsPageData(string $blogDetailCmsPageId): array
    {
        return [
            'id' => $blogDetailCmsPageId,
            'type' => 'page',
            'name' => 'Blog Detail',
            'sections' => [
                [
                    'id' => Uuid::randomHex(),
                    'type' => 'default',
                    'position' => 0,
                    'blocks' => [
                        [
                            'position' => 1,
                            'type' => 'blog-detail',
                            'slots' => [
                                ['type' => 'blog-detail', 'slot' => 'blogDetail'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }
}
