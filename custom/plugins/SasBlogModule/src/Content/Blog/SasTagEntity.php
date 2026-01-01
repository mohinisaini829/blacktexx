<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog;

use Sas\BlogModule\Content\Blog\SasTagTranslation\SasTagTranslationCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class SasTagEntity extends Entity
{
    use EntityIdTrait;

    protected ?SasTagTranslationCollection $translations = null;

    protected string $name;

    protected ?BlogEntriesCollection $blogs = null;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getTranslations(): ?SasTagTranslationCollection
    {
        return $this->translations;
    }

    public function setTranslations(?SasTagTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getBlogEntries(): ?BlogEntriesCollection
    {
        return $this->blogs;
    }

    public function setBlogEntries(?BlogEntriesCollection $blogs): void
    {
        $this->blogs = $blogs;
    }
}
