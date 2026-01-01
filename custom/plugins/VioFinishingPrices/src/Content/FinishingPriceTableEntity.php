<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class FinishingPriceTableEntity extends Entity
{
    use EntityIdTrait;
    /** @var string */
    protected $name;
    /** @var string|null */
    protected $text;
    /** @var bool */
    protected $active;
    /** @var int|null */
    protected $position;
    /** @var \Vio\FinishingPrices\Content\Aggregate\FinishingPriceTableTranslation\FinishingPriceTableTranslationCollection */
    protected $translations;

    public function setName(?string $value): void
    {
        $this->name = $value;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setText(?string $value): void
    {
        $this->text = $value;
    }

    public function getText(): ?string
    {
        return $this->text;
    }

    public function setActive(bool $value): void
    {
        $this->active = $value;
    }

    public function getActive(): bool
    {
        return $this->active;
    }

    public function setPosition(?int $value): void
    {
        $this->position = $value;
    }

    public function getPosition(): ?int
    {
        return $this->position;
    }

    public function setTranslations(?Aggregate\FinishingPriceTableTranslation\FinishingPriceTableTranslationCollection $value): void
    {
        $this->translations = $value;
    }

    public function getTranslations(): ?Aggregate\FinishingPriceTableTranslation\FinishingPriceTableTranslationCollection
    {
        return $this->translations;
    }
}
