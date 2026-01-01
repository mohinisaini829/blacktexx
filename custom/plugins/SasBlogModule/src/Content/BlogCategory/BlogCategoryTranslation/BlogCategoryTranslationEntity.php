<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\BlogCategory\BlogCategoryTranslation;

use Sas\BlogModule\Content\BlogCategory\BlogCategoryEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class BlogCategoryTranslationEntity extends TranslationEntity
{
    protected string $name;

    /**
     * @var array<string, mixed>|null
     */
    protected ?array $customFields;

    protected string $sasBlogCategoryId;

    protected ?BlogCategoryEntity $sasBlogCategory;

    /**
     * @var array<string>|null
     */
    protected ?array $breadcrumb;

    /**
     * @return string[]|null
     */
    public function getBreadcrumb(): ?array
    {
        return $this->breadcrumb;
    }

    /**
     * @param array<string>|null $breadcrumb
     */
    public function setBreadcrumb(?array $breadcrumb): void
    {
        $this->breadcrumb = $breadcrumb;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
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

    public function getSasBlogCategoryId(): string
    {
        return $this->sasBlogCategoryId;
    }

    public function setSasBlogCategoryId(string $sasBlogCategoryId): void
    {
        $this->sasBlogCategoryId = $sasBlogCategoryId;
    }

    public function getSasBlogCategory(): ?BlogCategoryEntity
    {
        return $this->sasBlogCategory;
    }

    public function setSasBlogCategory(BlogCategoryEntity $sasBlogCategory): void
    {
        $this->sasBlogCategory = $sasBlogCategory;
    }
}
