<?php declare(strict_types=1);

namespace Acris\CookieConsent\Custom\Aggregate\CookieTranslation;

use Acris\CookieConsent\Custom\CookieEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

class CookieTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $cookieId;

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
     * @var CookieEntity
     */
    protected $cookie;

    public function getCookieId(): string
    {
        return $this->cookieId;
    }

    public function setCookieId(string $cookieId): void
    {
        $this->cookieId = $cookieId;
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
     * @return string|null
     */
    public function getScript(): ?string
    {
        return $this->script;
    }

    /**
     * @param string|null $script
     */
    public function setScript(?string $script): void
    {
        $this->script = $script;
    }

    public function getCookie(): CookieEntity
    {
        return $this->cookie;
    }

    public function setCookie(CookieEntity $cookie): void
    {
        $this->cookie = $cookie;
    }
}
