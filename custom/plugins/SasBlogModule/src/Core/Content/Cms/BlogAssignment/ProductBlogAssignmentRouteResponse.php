<?php
declare(strict_types=1);

namespace Sas\BlogModule\Core\Content\Cms\BlogAssignment;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Shopware\Core\System\SalesChannel\StoreApiResponse;

/**
 * @extends StoreApiResponse<BlogEntriesCollection>
 */
class ProductBlogAssignmentRouteResponse extends StoreApiResponse
{
    public function __construct(BlogEntriesCollection $object)
    {
        parent::__construct($object);
    }

    public function getResult(): BlogEntriesCollection
    {
        return $this->object;
    }
}
