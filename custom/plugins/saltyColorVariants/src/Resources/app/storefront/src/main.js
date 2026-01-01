import SaltyColorVariantsUpdatePreview from './salty-color-variants/salty-color-variants-update-preview.plugin';
import SaltyColorVariantsInit from './salty-color-variants/salty-color-variants-init.plugin';
import SaltyColorVariantsFixLazyLoading from './salty-color-variants/salty-color-variants-fix-lazy-loading.plugin';

// Register them via the existing PluginManager
const PluginManager = window.PluginManager;
PluginManager.register('SaltyColorVariantsInit', SaltyColorVariantsInit, '.product-image');
PluginManager.register('SaltyColorVariantsUpdatePreview', SaltyColorVariantsUpdatePreview, '.color-variants--option');
PluginManager.register('SaltyColorVariantsFixLazyLoading', SaltyColorVariantsFixLazyLoading, '.color-variants--option img.weedesign-lazy-hidden');
