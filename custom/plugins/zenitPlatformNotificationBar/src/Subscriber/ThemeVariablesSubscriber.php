<?php declare(strict_types=1);

namespace zenit\PlatformNotificationBar\Subscriber;

use Shopware\Core\DevOps\Environment\EnvironmentHelper;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Theme\Event\ThemeCompilerEnrichScssVariablesEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ThemeVariablesSubscriber implements EventSubscriberInterface
{
    /**
     * @var SystemConfigService
     */
    protected $systemConfig;

    /**
     * @var string
     */
    private $configPath = 'zenitPlatformNotificationBar.config.';

    // add the `SystemConfigService` to your constructor
    public function __construct(SystemConfigService $systemConfig)
    {
        $this->systemConfig = $systemConfig;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ThemeCompilerEnrichScssVariablesEvent::class => 'onAddVariables',
        ];
    }

    public function onAddVariables(ThemeCompilerEnrichScssVariablesEvent $event): void
    {
        $dsn = trim((string) EnvironmentHelper::getVariable('DATABASE_URL', getenv('DATABASE_URL')));

        if ($dsn === '' || $dsn === 'mysql://_placeholder.test') {
            // deployment server without database
            return;
        }

        $shopId = $event->getSalesChannelId();

        /**
         * @var string $backgroundImageOpacity
         * @var string $backgroundBlur
         * @var string $backgroundBlendMode
         * @var string $backgroundRepeat
         * @var string $backgroundSize
         * @var string $backgroundPosition
         */
        $backgroundImageOpacity = $this->systemConfig->get($this->configPath . 'backgroundImageOpacity', $shopId);
        $backgroundBlur = $this->systemConfig->get($this->configPath . 'backgroundBlur', $shopId) ?? '0';
        $backgroundBlendMode = $this->systemConfig->get($this->configPath . 'backgroundBlendMode', $shopId);
        $backgroundRepeat = $this->systemConfig->get($this->configPath . 'backgroundRepeat', $shopId);
        $backgroundSize = $this->systemConfig->get($this->configPath . 'backgroundSize', $shopId);
        $backgroundPosition = $this->systemConfig->get($this->configPath . 'backgroundPosition', $shopId);

        /**
         * @var string $backgroundColor
         * @var string $backgroundColorOpacity
         */
        $backgroundColor = $this->systemConfig->get($this->configPath . 'backgroundColor', $shopId);
        $backgroundColorOpacity = $this->systemConfig->get($this->configPath . 'backgroundColorOpacity', $shopId);

        /**
         * @var string $textColor
         * @var string $font
         * @var string $fontStack
         * @var string $fontSize
         * @var string $fontWeight
         */
        $textColor = $this->systemConfig->get($this->configPath . 'textColor', $shopId);
        $font = $this->systemConfig->get($this->configPath . 'font', $shopId);
        $fontStack = $this->systemConfig->get($this->configPath . 'fontStack', $shopId);
        $fontSize = $this->systemConfig->get($this->configPath . 'fontSize', $shopId);
        $fontWeight = $this->systemConfig->get($this->configPath . 'fontWeight', $shopId);

        /**
         * @var string $padding
         * @var string $infoTextFontSize
         */
        $padding = $this->systemConfig->get($this->configPath . 'padding', $shopId) ?? '0';
        $infoTextFontSize = $this->systemConfig->get($this->configPath . 'infoTextFontSize', $shopId);

        /**
         * @var string $textSliderSpeed
         */
        $textSliderSpeed = $this->systemConfig->get($this->configPath . 'textSliderSpeed', $shopId);

        $event->addVariable('zen-notification-bar-background-image-opacity', $backgroundImageOpacity);
        $event->addVariable('zen-notification-bar-background-image-blur', $backgroundBlur . 'px');
        $event->addVariable('zen-notification-bar-background-image-blend-mode', $backgroundBlendMode);
        $event->addVariable('zen-notification-bar-background-image-repeat', $backgroundRepeat);
        $event->addVariable('zen-notification-bar-background-image-size', $backgroundSize);
        $event->addVariable('zen-notification-bar-background-image-position', $backgroundPosition);

        $event->addVariable('zen-notification-bar-background-color', $backgroundColor);
        $event->addVariable('zen-notification-bar-background-color-opacity', $backgroundColorOpacity);

        $event->addVariable('zen-notification-bar-text-color', $textColor);
        if (isset($font) && !empty($font)) {
            $event->addVariable('zen-notification-bar-font', $font);
        } else {
            $event->addVariable('zen-notification-bar-font', 'custom');
        }
        $event->addVariable('zen-notification-bar-font-stack', $fontStack);
        $event->addVariable('zen-notification-bar-font-size', $fontSize / 16 . 'rem');
        $event->addVariable('zen-notification-bar-font-weight', $fontWeight);

        $event->addVariable('zen-notification-bar-padding', $padding . 'px');
        $event->addVariable('zen-notification-bar-info-font-size', $infoTextFontSize / 16 . 'rem');
        $event->addVariable('zen-notification-bar-info-padding', $padding / 4 . 'px');

        $event->addVariable('zen-notification-bar-text-slider-speed', $textSliderSpeed . 'ms');
    }
}
