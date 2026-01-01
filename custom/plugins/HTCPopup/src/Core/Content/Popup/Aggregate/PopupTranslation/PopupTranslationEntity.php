<?php declare(strict_types=1);

namespace HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation;

use HTC\Popup\Core\Content\Popup\PopupEntity;
use Shopware\Core\Framework\DataAbstractionLayer\TranslationEntity;

/**
 * Class PopupTranslationEntity
 * @package HTC\Popup\Core\Content\Popup\Aggregate\PopupTranslation
 */
class PopupTranslationEntity extends TranslationEntity
{
    /**
     * @var string
     */
    protected $popupId;

    /**
     * @var PopupEntity
     */
    protected $popup;

    /**
     * @var string|null
     */
    protected $content;

     /**
     * @var string|null
     */
    protected $stores;

    /**
     * @return string
     */
    public function getPopupId(): string
    {
        return $this->popupId;
    }

    /**
     * @param string $popupId
     */
    public function setPopupId(string $popupId): void
    {
        $this->popupId = $popupId;
    }

    /**
     * @return PopupEntity
     */
    public function getPopup(): PopupEntity
    {
        return $this->popup;
    }

    /**
     * @param PopupEntity $popup
     */
    public function setPopup(PopupEntity $popup): void
    {
        $this->popup = $popup;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string|null $content
     */
    public function setContent(?string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string|null
     */
    public function getStores(): ?string
    {
        return $this->stores;
    }

    /**
     * @param string|null $stores
     */
    public function setStores(?string $stores): void
    {
        $this->stores = $stores;
    }
}
