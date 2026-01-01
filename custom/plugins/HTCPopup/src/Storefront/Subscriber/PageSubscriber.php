<?php declare(strict_types = 1);

namespace HTC\Popup\Storefront\Subscriber;

use HTC\Popup\Service\PopupService;
use HTC\Popup\Core\Content\Popup\PopupEntity;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Storefront\Page\Account\Login\AccountLoginPageLoadedEvent;
use Shopware\Storefront\Page\Account\Order\AccountOrderPageLoadedEvent;
use Shopware\Storefront\Page\Account\Profile\AccountProfilePageLoadedEvent;
use Shopware\Storefront\Page\Checkout\Cart\CheckoutCartPageLoadedEvent;
use Shopware\Storefront\Page\Navigation\NavigationPageLoadedEvent;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Storefront\Page\Search\SearchPageLoadedEvent;
use Shopware\Storefront\Page\Page;
use Shopware\Storefront\Page\PageLoadedEvent;

/**
 * Class PageSubscriber
 * @package HTC\Popup\Storefront\Subscriber
 */
class PageSubscriber implements EventSubscriberInterface
{
    /**
     * @var PopupService
     */
    private $popupService;

    /**
     * PageSubscriber constructor.
     * @param PopupService $popupService
     */
    public function __construct(
        PopupService $popupService
    ) {
        $this->popupService = $popupService;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents(): array
    {
        return [
            NavigationPageLoadedEvent::class => 'onPageLoaded',
            SearchPageLoadedEvent::class => 'onPageLoaded',
            AccountProfilePageLoadedEvent::class => 'onPageLoaded',
            ProductPageLoadedEvent::class => 'onPageLoaded',
            AccountLoginPageLoadedEvent::class => 'onPageLoaded',
            AccountOrderPageLoadedEvent::class => 'onPageLoaded',
            CheckoutCartPageLoadedEvent::class => 'onPageLoaded',
        ];
    }

    /**
     * @param PageLoadedEvent $event
     */
    public function onPageLoaded(PageLoadedEvent $event): void
    {
        $page = $event->getPage();
        try {
            $popupEntity = $this->popupService->getPopupsForLoadedPage($event);
            if (!$popupEntity) {
                return;
            }
            $contentPosition = $this->getContentPosition($popupEntity);
            $popupEntity->assign($contentPosition);
            if ($popupEntity->getBackgroundMedia()) {
                $popupBgrMedia = $popupEntity->getBackgroundMedia()->getMetaData();
                if (empty($popupEntity->getWidth())) {
                    $assignWidth = ($popupBgrMedia['width'] <= PopupEntity::POPUP_MAX_WIDTH) ? $popupBgrMedia['width'] : PopupEntity::POPUP_MAX_WIDTH;
                    $popupEntity->assign(['width' => $assignWidth]);
                }
                if (empty($popupEntity->getHeight())) {
                    $popupEntity->assign(['height' => $popupBgrMedia['height']]);
                }
                $popupEntity->assign(['ratio' => round($popupBgrMedia['width'] / $popupBgrMedia['height'],2)]);
            } else {
                $popupEntity->assign(['width' => PopupEntity::POPUP_MAX_WIDTH]);
            }

        } catch (InconsistentCriteriaIdsException $e) {
            return;
        }
        if (!$page instanceof Page) return;
        $page->addExtension('popup', $popupEntity);
        $event->getPage()->assign(
            [
                'storeId' => $event->getSalesChannelContext()->getSalesChannel()->getId()
            ]
        );  
    }

    /**
     * @param PopupEntity $popupEntity
     * @return string[]
     */
    protected function getContentPosition(PopupEntity $popupEntity)
    {
        $alignContent = $popupEntity->getAlignContent();
        switch ($alignContent) {
            case (PopupEntity::ALIGN_LEFT_TOP):
                return [
                    'justify' => "left",
                    'align'   => "top"
                ];
                break;
            case (PopupEntity::ALIGN_LEFT_CENTER):
                return [
                    'justify' => "left",
                    'align'   => "center"
                ];
                break;
            case (PopupEntity::ALIGN_LEFT_BOTTOM):
                return [
                    'justify' => "left",
                    'align'   => "bottom"
                ];
                break;
            case (PopupEntity::ALIGN_CENTER_TOP):
                return [
                    'justify' => "center",
                    'align'   => "top"
                ];
                break;
            case (PopupEntity::ALIGN_CENTER_CENTER):
                return [
                    'justify' => "center",
                    'align'   => "center"
                ];
                break;
            case (PopupEntity::ALIGN_CENTER_BOTTOM):
                return [
                    'justify' => "center",
                    'align'   => "bottom"
                ];
                break;
            case (PopupEntity::ALIGN_RIGHT_TOP):
                return [
                    'justify' => "right",
                    'align'   => "top"
                ];
                break;
            case (PopupEntity::ALIGN_RIGHT_CENTER):
                return [
                    'justify' => "right",
                    'align'   => "center"
                ];
                break;
            case (PopupEntity::ALIGN_RIGHT_BOTTOM):
                return [
                    'justify' => "right",
                    'align'   => "bottom"
                ];
                break;
            default:
                // Default value 
                return [
                    'justify' => "center",
                    'align'   => "center"
                ];
                break;
        }
    }
}
