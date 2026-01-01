<?php declare(strict_types=1);

namespace Santafatex\Brands\Core\Content\Brand;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class BrandEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var string
     */
    protected $name;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $sizeChartPath;

    /**
     * @var string|null
     */
    protected $videoSliderHtml;

    /**
     * @var string|null
     */
    protected $catalogPdfPath;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var int
     */
    protected $displayOrder;

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function getSizeChartPath(): ?string
    {
        return $this->sizeChartPath;
    }

    public function setSizeChartPath(?string $sizeChartPath): void
    {
        $this->sizeChartPath = $sizeChartPath;
    }

    public function getVideoSliderHtml(): ?string
    {
        return $this->videoSliderHtml;
    }

    public function setVideoSliderHtml(?string $videoSliderHtml): void
    {
        $this->videoSliderHtml = $videoSliderHtml;
    }

    public function getCatalogPdfPath(): ?string
    {
        return $this->catalogPdfPath;
    }

    public function setCatalogPdfPath(?string $catalogPdfPath): void
    {
        $this->catalogPdfPath = $catalogPdfPath;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    public function getDisplayOrder(): int
    {
        return $this->displayOrder;
    }

    public function setDisplayOrder(int $displayOrder): void
    {
        $this->displayOrder = $displayOrder;
    }
}
