<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Subscriber;

use Shopware\Core\Framework\DataAbstractionLayer\Exception\InconsistentCriteriaIdsException;
use Shopware\Core\Framework\Struct\ArrayStruct;
use Shopware\Core\Framework\Uuid\Exception\InvalidUuidException;
use Shopware\Core\System\SystemConfig\Exception\InvalidDomainException;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Event\StorefrontRenderEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use zenit\PlatformNotificationBar\Service\GetControllerInfo;

class StorefrontRenderSubscriber implements EventSubscriberInterface
{
    /**
     * @var string
     */
    private $pluginName = 'zenitPlatformNotificationBar';

    /**
     * @var string
     */
    private $configPath = 'zenitPlatformNotificationBar.config';

    /**
     * StorefrontRenderSubscriber constructor.
     */
    public function __construct(private readonly SystemConfigService $systemConfigService, private readonly GetControllerInfo $getControllerInfo)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            StorefrontRenderEvent::class => 'onStorefrontRender',
        ];
    }

    /**
     * @throws InvalidDomainException
     * @throws InvalidUuidException
     * @throws InconsistentCriteriaIdsException
     */
    public function onStorefrontRender(StorefrontRenderEvent $event): void
    {
        if (\in_array($event->getRequest()->get('_route'), ['frontend.header', 'frontend.footer'], true)) {
            return;
        }

        $shopId = $event->getSalesChannelContext()->getSalesChannel()->getId();

        // is active check
        if (!$this->systemConfigService->get($this->configPath . '.active', $shopId)) {
            return;
        }

        // controller check
        $currentController = $this->getControllerInfo->getCurrentController();
        $allowedControllers = $this->systemConfigService->get($this->configPath . '.allowedControllers', $shopId);

        // remove deprecated controllers
        if (\is_array($allowedControllers)) {
            $allowedControllers = array_diff($allowedControllers, ['Search', 'AccountProfile', 'Cms.page']);
        }

        if (!empty($allowedControllers) && !\in_array($currentController, $allowedControllers, true)) {
            return;
        }

        // get config
        $config = $this->systemConfigService->get($this->configPath, $shopId);

        // add banner id for StorageKey based on plugin-configuration
        $config['configId'] = self::generateConfigId($config);

        // add config
        $event->getContext()->addExtension($this->pluginName, new ArrayStruct(['config' => $config]));
    }

    private function generateConfigId($config)
    {
        $id = null;
        foreach ($config as $key => $value) {
            if (\is_array($value)) {
                $value = implode('', $value);
            }

            if (\is_bool($value)) {
                $value = $value ? 'true' : 'false';
            }

            $id .= \strlen((string) $value);
        }

        return $id;
    }
}
