<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\BlogAuthor\BlogAuthorTranslation;

use Sas\BlogModule\Content\BlogAuthor\BlogAuthorEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class BlogAuthorTranslationEntity extends TranslationEntity
{
    protected string $sasBlogAuthorId;

    protected ?BlogAuthorEntity $sasBlogAuthor;

    protected string $description;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $customFields;

    public function getSasBlogAuthorId(): string
    {
        return $this->sasBlogAuthorId;
    }

    public function setSasBlogAuthorId(string $sasBlogAuthorId): void
    {
        $this->sasBlogAuthorId = $sasBlogAuthorId;
    }

    public function getSasBlogAuthor(): ?BlogAuthorEntity
    {
        return $this->sasBlogAuthor;
    }

    public function setSasBlogAuthor(BlogAuthorEntity $sasBlogAuthor): void
    {
        $this->sasBlogAuthor = $sasBlogAuthor;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getCustomFields(): ?array
    {
        return $this->customFields;
    }

    /**
     * @param array<string, mixed>|null $customFields
     */
    public function setCustomFields(?array $customFields): void
    {
        $this->customFields = $customFields;
    }
}
