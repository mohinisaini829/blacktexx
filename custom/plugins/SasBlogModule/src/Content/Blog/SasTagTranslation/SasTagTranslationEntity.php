<?php
declare(strict_types=1);

namespace Sas\BlogModule\Content\Blog\SasTagTranslation;

use Sas\BlogModule\Content\Blog\SasTagEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class SasTagTranslationEntity extends TranslationEntity
{
    protected string $sasTagId;

    protected SasTagEntity $sasTagEntity;

    protected string $name;

    public function getSasTagId(): string
    {
        return $this->sasTagId;
    }

    public function setSasTagId(string $sasTagId): void
    {
        $this->sasTagId = $sasTagId;
    }

    public function getSasTagEntity(): SasTagEntity
    {
        return $this->sasTagEntity;
    }

    public function setSasTagEntity(SasTagEntity $sasTagEntity): void
    {
        $this->sasTagEntity = $sasTagEntity;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
