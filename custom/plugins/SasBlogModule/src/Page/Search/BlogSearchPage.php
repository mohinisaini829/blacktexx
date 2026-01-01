<?php
declare(strict_types=1);

namespace Sas\BlogModule\Page\Search;

use Sas\BlogModule\Content\Blog\BlogEntriesCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Search\EntitySearchResult;
use Shopware\Storefront\Page\Page;

class BlogSearchPage extends Page
{
    protected string $searchTerm;

    /**
     * @var EntitySearchResult<BlogEntriesCollection>
     */
    protected EntitySearchResult $listing;

    public function getSearchTerm(): string
    {
        return $this->searchTerm;
    }

    public function setSearchTerm(string $searchTerm): void
    {
        $this->searchTerm = $searchTerm;
    }

    /**
     * @return EntitySearchResult<BlogEntriesCollection>
     */
    public function getListing(): EntitySearchResult
    {
        return $this->listing;
    }

    /**
     * @param EntitySearchResult<BlogEntriesCollection> $listing
     */
    public function setListing(EntitySearchResult $listing): void
    {
        $this->listing = $listing;
    }
}
