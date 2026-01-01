<?php declare(strict_types=1);

namespace HTC\Popup\Core\Content\Popup;

use Shopware\Core\Framework\DataAbstractionLayer\Entity;
use Shopware\Core\Framework\DataAbstractionLayer\EntityIdTrait;
use Shopware\Core\Content\Media\MediaEntity;

/**
 * Class PopupEntity
 * @package HTC\Popup\Core\Content\Popup
 */
class PopupEntity extends Entity
{
    /**
     * Const 
     */
    const INACTIVE_STATUS = 0;
    /**
     * Const
     */
    const ACTIVE_STATUS = 1;
    /**
     * Const
     */
    const HOMEPAGE_VISIBLE_PAGE = 0;
    /**
     * Const
     */
    const PRODUCTPAGE_VISIBLE_PAGE = 1;
    /**
     * Const
     */
    const CATEGORYPAGE_VISIBLE_PAGE = 2;
    /**
     * Const
     */
    const OTHERPAGE_VISIBLE_PAGE = 3;
    /**
     * Const
     */
    const ALIGN_LEFT_TOP = 1;
    /**
     * Const
     */
    const ALIGN_LEFT_CENTER = 2;
    /**
     * Const
     */
    const ALIGN_LEFT_BOTTOM = 3;
    /**
     * Const
     */
    const ALIGN_CENTER_TOP = 4;
    /**
     * Const
     */
    const ALIGN_CENTER_CENTER = 5;
    /**
     * Const
     */
    const ALIGN_CENTER_BOTTOM = 6;
    /**
     * Const
     */
    const ALIGN_RIGHT_TOP = 7;
    /**
     * Const
     */
    const ALIGN_RIGHT_CENTER = 8;
    /**
     * Const
     */
    const ALIGN_RIGHT_BOTTOM = 9;
    /**
     * Const
     */
    const POPUP_MAX_WIDTH = 1000;

    use EntityIdTrait;

    /**
     * @var bool
     */
    protected $active;

    /**
     * @var string|null
     */
    protected $title;

    /**
     * @var string|null
     */
    protected $visibleOn;

    /**
     * @var bool
     */
    protected $showGuest;

    /**
     * @var string|null
     */
    protected $customerGroupIds;

    /**
     * @var int
     */
    protected $priority;

    /**
     * @var int
     */
    protected $frequency;

    /**
     * @var string|null
     */
    protected $content;

    /**
     * @var string|null
     */
    protected $css;

    /**
     * @var MediaEntity
     */
    protected $backgroundMedia;

    /**
     * @var string|null
     */
    protected $backgroundMediaId;

    /**
     * @var string|null
     */
    protected $className;

    /**
     * @var string|null
     */
    protected $textColor;

    /**
     * @var int
     */
    protected $view;

    /**
     * @var int
     */
    protected $click;

    /**
     * @var float
     */
    protected $ctr;

    /**
     * @var int|null
     */
    protected $width;

    /**
     * @var int|null
     */
    protected $height;

    /**
     * @var int|null
     */
    protected $alignContent;

    /**
     * @var bool
     */
    protected $isRedirect;

    /**
     * @var string|null
     */
    protected $confirmButtonTitle;

    /**
     * @var string|null
     */
    protected $denyButtonTitle;

    /**
     * @var string|null
     */
    protected $denyButtonLink;

    /**
     * @var string|null
     */
    protected $backgroundColorButton;

    /**
     * @return bool
     */
    public function getActive(): bool
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
     * @return string
     */
    public function getTitle(): string
    {
        return $this->title;
    }

    /**
     * @param string $title
     */
    public function setTitle(string $title): void
    {
        $this->title = $title;
    }

    /**
     * @return string|null
     */
    public function getVisibleOn(): ?string
    {
        return $this->visibleOn;
    }

    /**
     * @param string $visibleOn
     */
    public function setVisibleOn(string $visibleOn): void
    {
        $this->visibleOn = $visibleOn;
    }

    /**
     * @return bool
     */
    public function getShowGuest(): bool
    {
        return $this->showGuest;
    }

    /**
     * @param bool $showGuest
     */
    public function setShowGuest(bool $showGuest): void
    {
        $this->showGuest = $showGuest;
    }

    /**
     * @return string|null
     */
    public function getCustomerGroupIds(): ?string
    {
        return $this->customerGroupIds;
    }

    /**
     * @param string $customerGroupIds
     */
    public function setCustomerGroupIds(string $customerGroupIds): void
    {
        $this->customerGroupIds = $customerGroupIds;
    }

    /**
     * @return int
     */
    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * @param int $priority
     */
    public function setPriority(int $priority): void
    {
        $this->priority = $priority;
    }

    /**
     * @return int
     */
    public function getFrequency(): int
    {
        return $this->frequency;
    }

    /**
     * @param int $frequency
     */
    public function setFrequency(int $frequency): void
    {
        $this->frequency = $frequency;
    }

    /**
     * @return string|null
     */
    public function getContent(): ?string
    {
        return $this->content;
    }

    /**
     * @param string $content
     */
    public function setContent(string $content): void
    {
        $this->content = $content;
    }

    /**
     * @return string|null
     */
    public function getCss(): ?string
    {
        return $this->css;
    }

    /**
     * @param string $css
     */
    public function setCss(string $css): void
    {
        $this->css = $css;
    }

    /**
     * @return MediaEntity|null
     */
    public function getBackgroundMedia(): ?MediaEntity
    {
        return $this->backgroundMedia;
    }

    /**
     * @param MediaEntity $backgroundMedia
     */
    public function setBackgroundMedia(MediaEntity $backgroundMedia): void
    {
        $this->backgroundMedia = $backgroundMedia;
    }

    /**
     * @return string|null
     */
    public function getBackgroundMediaId(): ?string
    {
        return $this->backgroundMediaId;
    }

    /**
     * @param string $backgroundMediaId
     */
    public function setBackgroundMediaId(string $backgroundMediaId): void
    {
        $this->backgroundMediaId = $backgroundMediaId;
    }

    /**
     * @return string|null
     */
    public function getClassName(): ?string
    {
        return $this->className;
    }

    /**
     * @param string $className
     */
    public function setClassName(string $className): void
    {
        $this->className = $className;
    }

    /**
     * @return string|null
     */
    public function getTextColor(): ?string
    {
        return $this->textColor;
    }

    /**
     * @param string $textColor
     */
    public function setTextColor(string $textColor): void
    {
        $this->textColor = $textColor;
    }

    /**
     * @return int
     */
    public function getView(): int
    {
        return $this->view;
    }

    /**
     * @param int $view
     */
    public function setView(int $view): void
    {
        $this->view = $view;
    }

    /**
     * @return int
     */
    public function getClick(): int
    {
        return $this->click;
    }

    /**
     * @param int $click
     */
    public function setClick(int $click): void
    {
        $this->click = $click;
    }

    /**
     * @return float
     */
    public function getCtr(): float
    {
        return $this->ctr;
    }

    /**
     * @param float $ctr
     */
    public function setCtr(float $ctr): void
    {
        $this->ctr = $ctr;
    }

    /**
     * @return int|null
     */
    public function getWidth(): ?int
    {
        return $this->width;
    }

    /**
     * @param int $width
     */
    public function setWidth(int $width): void
    {
        $this->width = $width;
    }

    /**
     * @return int|null
     */
    public function getHeight(): ?int
    {
        return $this->height;
    }

    /**
     * @param int $height
     */
    public function setHeight(int $height): void
    {
        $this->height = $height;
    }

    /**
     * @return int|null
     */
    public function getAlignContent(): ?int
    {
        return $this->alignContent;
    }

    /**
     * @param int $alignContent
     */
    public function setAlignContent(int $alignContent): void
    {
        $this->alignContent = $alignContent;
    }

    /**
     * @return bool
     */
    public function getIsRedirect(): bool
    {
        return $this->isRedirect;
    }

    /**
     * @param bool $isRedirect
     */
    public function setIsRedirect(bool $isRedirect): void
    {
        $this->isRedirect = $isRedirect;
    }

    /**
     * @return string|null
     */
    public function getConfirmButtonTitle(): ?string
    {
        return $this->confirmButtonTitle;
    }

    /**
     * @param string $confirmButtonTitle
     */
    public function setConfirmButtonTitle(string $confirmButtonTitle): void
    {
        $this->confirmButtonTitle = $confirmButtonTitle;
    }

    /**
     * @return string|null
     */
    public function getDenyButtonTitle(): ?string
    {
        return $this->denyButtonTitle;
    }

    /**
     * @param string $denyButtonTitle
     */
    public function setDenyButtonTitle(string $denyButtonTitle): void
    {
        $this->denyButtonTitle = $denyButtonTitle;
    }

    /**
     * @return string|null
     */
    public function getDenyButtonLink(): ?string
    {
        return $this->denyButtonLink;
    }

    /**
     * @param string $denyButtonLink
     */
    public function setDenyButtonLink(string $denyButtonLink): void
    {
        $this->denyButtonLink = $denyButtonLink;
    }

    /**
     * @return string|null
     */
    public function getBackgroundColorButton(): ?string
    {
        return $this->backgroundColorButton;
    }

    /**
     * @param string $backgroundColorButton
     */
    public function setBackgroundColorButton(string $backgroundColorButton): void
    {
        $this->backgroundColorButton = $backgroundColorButton;
    }
}
