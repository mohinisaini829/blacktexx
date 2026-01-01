import ReplaceOffCanvasTabsPlugin from './plugin/offcanvas-tabs/replace-offcanvas-tabs.plugin';
import NetGrossSwitchPlugin from './plugin/net-gross-switch.plugin';
import MultipleItemsGallerySliderPlugin from './plugin/slider/multiple-items-gallery-slider.plugin';
import ContactFormRedirectPlugin from './plugin/contact-redirect/contact-form-redirect.plugin'
import SantaDiscountModalPlugin from './plugin/shopLoad-modal/shopLoad-modal.plugin';
import PluginManager from 'src/plugin-system/plugin.manager';

PluginManager.override('OffCanvasTabs', ReplaceOffCanvasTabsPlugin, '[data-offcanvas-tabs]');
PluginManager.register('NetGrossSwitchPlugin', NetGrossSwitchPlugin, '.net-gross-switch');
PluginManager.override('GallerySlider', MultipleItemsGallerySliderPlugin, '[data-gallery-slider]');
PluginManager.register('ContactFormRedirectPlugin', ContactFormRedirectPlugin, '[data-contact-form]');
PluginManager.register('SantaDiscountModalPlugin', SantaDiscountModalPlugin, '[data-santa-discount]');
