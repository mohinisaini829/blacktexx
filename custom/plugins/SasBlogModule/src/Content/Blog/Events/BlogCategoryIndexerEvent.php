<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\Events;

use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\Event\NestedEvent;

class BlogCategoryIndexerEvent extends NestedEvent
{
    /**
     * @param array<string> $ids
     * @param array<string> $skip
     */
    public function __construct(private readonly array $ids, private readonly Context $context, private readonly array $skip = [])
    {
    }

    /**
     * @return array<string>
     */
    public function getIds(): array
    {
        return $this->ids;
    }

    public function getContext(): Context
    {
        return $this->context;
    }

    /**
     * @return array<string>
     */
    public function getSkip(): array
    {
        return $this->skip;
    }
}
