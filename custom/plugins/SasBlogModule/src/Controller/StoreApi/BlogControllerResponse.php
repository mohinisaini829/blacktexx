<?php
declare(strict_types=1);

namespace Sas\BlogModule\Controller\StoreApi;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @extends StoreApiResponse<EntitySearchResult<BlogEntriesCollection>>
 */
class BlogControllerResponse extends StoreApiResponse
{
    public function __construct(EntitySearchResult $object)
    {
        parent::__construct($object);
    }

    public function getBlogEntries(): BlogEntriesCollection
    {
        return $this->object->getEntities();
    }
}
