<?php

declare(strict_types=1);

namespace Vio\FinishingPrices\Content\Aggregate\FinishingPriceTableTranslation;

use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class FinishingPriceTableTranslationEntity extends TranslationEntity
{
    use EntityIdTrait;

    protected ?string $name = null;

    protected ?string $text = null;

    public function setName(string $value): void
    {
        $this->name = $value;
    }

    public function getName(): string
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
}
