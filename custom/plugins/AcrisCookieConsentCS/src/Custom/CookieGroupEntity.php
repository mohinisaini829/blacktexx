<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation\CookieGroupTranslationCollection;
use Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation\CookieGroupTranslationEntity;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;

class CookieGroupEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var CookieCollection|null
     */
    protected $cookies;

    /**
     * @var boolean|null
     */
    protected $isDefault;

    /**
     * @var string|null
     */
    protected $identification;

    /**
     * @var CookieGroupTranslationCollection|null
     */
    protected $translations;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @return CookieCollection|null
     */
    public function getCookies(): ?CookieCollection
    {
        return $this->cookies;
    }

    /**
     * @param CookieCollection|null $cookies
     */
    public function setCookies(?CookieCollection $cookies): void
    {
        $this->cookies = $cookies;
    }

    /**
     * @return bool
     */
    public function isDefault(): ?bool
    {
        return $this->isDefault;
    }

    /**
     * @param bool $isDefault
     */
    public function setIsDefault(bool $isDefault): void
    {
        $this->isDefault = $isDefault;
    }

    /**
     * @return string|null
     */
    public function getIdentification(): ?string
    {
        return $this->identification;
    }

    /**
     * @param string|null $identification
     */
    public function setIdentification(?string $identification): void
    {
        $this->identification = $identification;
    }

    /**
     * @return CookieGroupTranslationCollection|null
     */
    public function getTranslations(): ?CookieGroupTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param CookieGroupTranslationCollection|null $translations
     */
    public function setTranslations(?CookieGroupTranslationCollection $translations): void
    {
        $this->translations = $translations;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }
}
