<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use Sas\BlogModule\Content\BlogCategory\BlogCategoryCollection;
use Shopware\Core\Content\Category\CategoryException;
use Shopware\Core\Content\Category\Exception\CategoryNotFoundException;
use Shopware\Core\Defaults;
use Shopware\Core\Framework\Api\Context\SystemSource;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Doctrine\RetryableQuery;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\Uuid\Uuid;
use Shopware\Core\System\Language\LanguageCollection;
use Shopware\Core\System\Language\LanguageEntity;

class BlogCategoryBreadcrumbUpdater
{
    /**
     * @internal
     *
     * @param EntityRepository<BlogCategoryCollection> $categoryRepository
     * @param EntityRepository<LanguageCollection>     $languageRepository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly EntityRepository $categoryRepository,
        private readonly EntityRepository $languageRepository
    ) {
    }

    /**
     * @param string[] $ids
     */
    public function update(array $ids, Context $context): void
    {
        if (empty($ids)) {
            return;
        }

        $versionId = Uuid::fromHexToBytes($context->getVersionId());

        $query = $this->connection->createQueryBuilder();
        $query->select('sas_blog_category.path');
        $query->from('sas_blog_category');
        $query->where('sas_blog_category.id IN (:ids)');
        $query->andWhere('sas_blog_category.version_id = :version');
        $query->setParameter('version', $versionId);
        $query->setParameter('ids', Uuid::fromHexToBytesList($ids), ArrayParameterType::BINARY);

        $paths = $query->executeQuery()->fetchFirstColumn();

        $all = $ids;
        foreach ($paths as $path) {
            $path = explode('|', (string) $path);
            foreach ($path as $id) {
                $all[] = $id;
            }
        }

        $all = array_filter(array_keys(array_flip($all)));

        $languages = $this->languageRepository->search(new Criteria(), $context);

        /** @var LanguageEntity $language */
        foreach ($languages as $language) {
            $context = new Context(
                new SystemSource(),
                [],
                Defaults::CURRENCY,
                array_filter([$language->getId(), $language->getParentId(), Defaults::LANGUAGE_SYSTEM]),
                Defaults::LIVE_VERSION
            );

            $this->updateLanguage($ids, $context, $all);
        }
    }

    /**
     * @param string[] $ids
     * @param string[] $all
     */
    private function updateLanguage(array $ids, Context $context, array $all): void
    {
        $versionId = Uuid::fromHexToBytes($context->getVersionId());
        $languageId = Uuid::fromHexToBytes($context->getLanguageId());

        /** @var BlogCategoryCollection $categories */
        $categories = $this->categoryRepository
            ->search(new Criteria($all), $context)
            ->getEntities();

        $update = $this->connection->prepare('
            INSERT INTO `sas_blog_category_translation` (`sas_blog_category_id`, `sas_blog_category_version_id`, `language_id`, `breadcrumb`, `created_at`)
            VALUES (:sasBlogCategoryId, :sasBlogCategoryVersionId, :languageId, :breadcrumb, DATE(NOW()))
            ON DUPLICATE KEY UPDATE `breadcrumb` = :breadcrumb
        ');
        $update = new RetryableQuery($this->connection, $update);

        foreach ($ids as $id) {
            try {
                $path = $this->buildBreadcrumb($id, $categories);
            } catch (CategoryNotFoundException) {
                continue;
            }

            $update->execute([
                'sasBlogCategoryId' => Uuid::fromHexToBytes($id),
                'sasBlogCategoryVersionId' => $versionId,
                'languageId' => $languageId,
                'breadcrumb' => json_encode($path, \JSON_THROW_ON_ERROR),
            ]);
        }
    }

    /**
     * @return array<string, string>
     */
    private function buildBreadcrumb(string $id, BlogCategoryCollection $categories): array
    {
        $category = $categories->get($id);

        if (!$category) {
            throw CategoryException::categoryNotFound($id);
        }

        $breadcrumb = [];
        if ($category->getParentId()) {
            $breadcrumb = $this->buildBreadcrumb($category->getParentId(), $categories);
        }

        $breadcrumb[$category->getId()] = $category->getTranslation('name');

        return $breadcrumb;
    }
}
