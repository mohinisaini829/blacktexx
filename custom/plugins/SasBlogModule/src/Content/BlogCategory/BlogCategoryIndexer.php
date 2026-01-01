<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\BlogCategory;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception;
use Psr\EventDispatcher\EventDispatcherInterface;
use Sas\BlogModule\Content\Blog\BlogCategoryBreadcrumbUpdater;
use Sas\BlogModule\Content\Blog\Events\BlogCategoryIndexerEvent;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\Dbal\Common\IteratorFactory;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Event\EntityWrittenContainerEvent;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\ChildCountUpdater;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexer;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\EntityIndexingMessage;
use Shopware\Core\Framework\DataAbstractionLayer\Indexing\TreeUpdater;
use Shopware\Core\Framework\Plugin\Exception\DecorationPatternException;
use Shopware\Core\Framework\Uuid\Uuid;

class BlogCategoryIndexer extends EntityIndexer
{
    /**
     * @param EntityRepository<BlogCategoryCollection> $repository
     */
    public function __construct(
        private readonly Connection $connection,
        private readonly IteratorFactory $iteratorFactory,
        private readonly EntityRepository $repository,
        private readonly ChildCountUpdater $childCountUpdater,
        private readonly TreeUpdater $treeUpdater,
        private readonly BlogCategoryBreadcrumbUpdater $breadcrumbUpdater,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    public function getName(): string
    {
        return 'sas.blog.category.indexer';
    }

    public function iterate($offset): ?EntityIndexingMessage
    {
        $iterator = $this->iteratorFactory->createIterator($this->repository->getDefinition(), $offset);

        $ids = $iterator->fetch();

        if (empty($ids)) {
            return null;
        }

        return new EntityIndexingMessage(array_values($ids), $iterator->getOffset());
    }

    public function update(EntityWrittenContainerEvent $event): ?EntityIndexingMessage
    {
        $categoryEvent = $event->getEventByEntityName(BlogCategoryDefinition::ENTITY_NAME);

        if (!$categoryEvent) {
            return null;
        }

        $ids = $categoryEvent->getIds();
        foreach ($categoryEvent->getWriteResults() as $result) {
            if (!$result->getExistence()) {
                continue;
            }
            $state = $result->getExistence()->getState();

            if (isset($state['parent_id'])) {
                $ids[] = Uuid::fromBytesToHex($state['parent_id']);
            }

            $payload = $result->getPayload();
            if (isset($payload['parentId'])) {
                $ids[] = $payload['parentId'];
            }
        }

        if (empty($ids)) {
            return null;
        }

        // tree should be updated immediately
        $this->treeUpdater->batchUpdate($ids, BlogCategoryDefinition::ENTITY_NAME, $event->getContext(), true);

        $children = $this->fetchChildren($ids, $event->getContext()->getVersionId());

        $ids = array_unique(array_merge($ids, $children));

        return new EntityIndexingMessage(array_values($ids), null, $event->getContext(), \count($ids) > 20);
    }

    public function handle(EntityIndexingMessage $message): void
    {
        $ids = $message->getData();
        if (\is_string($ids)) {
            $ids = [$ids];
        }

        $ids = array_unique(array_filter($ids));
        if (empty($ids)) {
            return;
        }

        $context = Context::createDefaultContext();

        $this->connection->beginTransaction();

        // listen to parent id changes
        $this->childCountUpdater->update(BlogCategoryDefinition::ENTITY_NAME, $ids, $context);

        // listen to parent id changes
        $this->treeUpdater->batchUpdate($ids, BlogCategoryDefinition::ENTITY_NAME, $context, true);

        $this->breadcrumbUpdater->update($ids, $context);

        $this->connection->commit();

        $this->eventDispatcher->dispatch(new BlogCategoryIndexerEvent($ids, $message->getContext(), $message->getSkip()));
    }

    public function getTotal(): int
    {
        return $this->iteratorFactory->createIterator($this->repository->getDefinition())->fetchCount();
    }

    public function getDecorated(): EntityIndexer
    {
        throw new DecorationPatternException(static::class);
    }

    /**
     * @param array<string> $categoryIds
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws Exception
     */
    private function fetchChildren(array $categoryIds, string $versionId): array
    {
        $query = $this->connection->createQueryBuilder();
        $query->select('DISTINCT LOWER(HEX(category.id))');
        $query->from('category');

        $wheres = [];
        foreach ($categoryIds as $id) {
            $key = 'path' . $id;
            $wheres[] = 'category.path LIKE :' . $key;
            $query->setParameter($key, '%|' . $id . '|%');
        }

        $query->andWhere('(' . implode(' OR ', $wheres) . ')');
        $query->andWhere('category.version_id = :version');
        $query->setParameter('version', Uuid::fromHexToBytes($versionId));
        $result = $query->executeQuery();

        return $result->fetchAllAssociative();
    }
}
