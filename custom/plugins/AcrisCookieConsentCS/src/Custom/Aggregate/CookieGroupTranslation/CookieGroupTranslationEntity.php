<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieGroupTranslation;

use Acris\CookieConsent\Custom\CookieGroupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class CookieGroupTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $cookieGroupId;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $description;

    /**
     * @var CookieGroupEntity
     */
    protected $cookieGroup;

    /**
     * @return string
     */
    public function getCookieGroupId(): string
    {
        return $this->cookieGroupId;
    }

    /**
     * @param string $cookieGroupId
     */
    public function setCookieGroupId(string $cookieGroupId): void
    {
        $this->cookieGroupId = $cookieGroupId;
    }

    /**
     * @return string|null
     */
    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * @param string|null $title
     */
    public function setTitle(?string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * @param string|null $description
     */
    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    /**
     * @return CookieGroupEntity
     */
    public function getCookieGroup(): CookieGroupEntity
    {
        return $this->cookieGroup;
    }

    /**
     * @param CookieGroupEntity $cookieGroup
     */
    public function setCookieGroup(CookieGroupEntity $cookieGroup): void
    {
        $this->cookieGroup = $cookieGroup;
    }
}
