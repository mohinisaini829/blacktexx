<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom;

use Acris\CookieConsent\Custom\Aggregate\CookieTranslation\CookieTranslationCollection;
use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Framework\DataAbstractionLayer\Field\JsonField;
use Shopware\Core\System\SalesChannel\SalesChannelCollection;

class CookieEntity extends Entity
{
    use EntityIdTrait;

    /**
     * @var SalesChannelCollection|null
     */
    protected $salesChannels;

    /**
     * @var string|null
     */
    protected $cookieGroupId;

    /**
     * @var CookieGroupEntity|null
     */
    protected $cookieGroup;

    /**
     * @var string
     */
    protected $cookieId;

    /**
     * @var string|null
     */
    protected $defaultValue;

    /**
     * @var string|null
     */
    protected $provider;

    /**
     * @var boolean
     */
    protected $active;

    /**
     * @var boolean
     */
    protected $unknown;

    /**
     * @var boolean
     */
    protected $isDefault;

    /**
     * @var string|null
     */
    protected $scriptPosition;

    /**
     * @var CookieTranslationCollection|null
     */
    protected $translations;

    /**
     * @var array
     */
    protected $googleCookieConsentMode;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var string|null
     */
    protected $script;

    /**
     * @var bool
     */
    protected $fromExtension;

    /**
     * @return array
     */
    public function getGoogleCookieConsentMode(): array
    {
        return $this->googleCookieConsentMode;
    }

    /**
     * @param array $googleCookieConsentMode
     */
    public function setGoogleCookieConsentMode(array $googleCookieConsentMode): void
    {
        $this->googleCookieConsentMode = $googleCookieConsentMode;
    }

    /**
     * @return SalesChannelCollection|null
     */
    public function getSalesChannels(): ?SalesChannelCollection
    {
        return $this->salesChannels;
    }

    /**
     * @param SalesChannelCollection|null $salesChannels
     */
    public function setSalesChannels(?SalesChannelCollection $salesChannels): void
    {
        $this->salesChannels = $salesChannels;
    }

    /**
     * @return string|null
     */
    public function getCookieGroupId(): ?string
    {
        return $this->cookieGroupId;
    }

    /**
     * @param string|null $cookieGroupId
     */
    public function setCookieGroupId(?string $cookieGroupId): void
    {
        $this->cookieGroupId = $cookieGroupId;
    }

    /**
     * @return CookieGroupEntity|null
     */
    public function getCookieGroup(): ?CookieGroupEntity
    {
        return $this->cookieGroup;
    }

    /**
     * @param CookieGroupEntity|null $cookieGroup
     */
    public function setCookieGroup(?CookieGroupEntity $cookieGroup): void
    {
        $this->cookieGroup = $cookieGroup;
    }

    /**
     * @return string
     */
    public function getCookieId(): string
    {
        return $this->cookieId;
    }

    /**
     * @param string $cookieId
     */
    public function setCookieId(string $cookieId): void
    {
        $this->cookieId = $cookieId;
    }

    /**
     * @return string|null
     */
    public function getDefaultValue(): ?string
    {
        return $this->defaultValue;
    }

    /**
     * @param string|null $defaultValue
     */
    public function setDefaultValue(?string $defaultValue): void
    {
        $this->defaultValue = $defaultValue;
    }

    /**
     * @return string|null
     */
    public function getProvider(): ?string
    {
        return $this->provider;
    }

    /**
     * @param string|null $provider
     */
    public function setProvider(?string $provider): void
    {
        $this->provider = $provider;
    }

    /**
     * @return bool
     */
    public function isActive(): bool
    {
        return $this->active;
    }

    /**
     * @param bool $active
     */
    public function setActive(bool $active): void
    {
        $this->active = $active;
    }

    /**
     * @return bool
     */
    public function isUnknown(): bool
    {
        return $this->unknown;
    }

    /**
     * @param bool $unknown
     */
    public function setUnknown(bool $unknown): void
    {
        $this->unknown = $unknown;
    }

    /**
     * @return bool
     */
    public function isDefault(): bool
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
    public function getScriptPosition(): ?string
    {
        return $this->scriptPosition;
    }

    /**
     * @param string|null $scriptPosition
     */
    public function setScriptPosition(?string $scriptPosition): void
    {
        $this->scriptPosition = $scriptPosition;
    }

    /**
     * @return CookieTranslationCollection|null
     */
    public function getTranslations(): ?CookieTranslationCollection
    {
        return $this->translations;
    }

    /**
     * @param CookieTranslationCollection|null $translations
     */
    public function setTranslations(?CookieTranslationCollection $translations): void
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

    public function getScript(): ?string
    {
        return $this->script;
    }

    public function setScript(?string $script): void
    {
        $this->script = $script;
    }

    public function isFromExtension(): bool
    {
        return $this->fromExtension;
    }

    public function setFromExtension(bool $fromExtension): void
    {
        $this->fromExtension = $fromExtension;
    }
}
