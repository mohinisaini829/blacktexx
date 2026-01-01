import BilobaArticleVariantOrderMatrix from '../biloba/article-variant-order-matrix/biloba-article-variant-order-matrix.plugin.js';
import BilobaArticleVariantOrderAddToCart from '../biloba/article-variant-order-matrix/biloba-article-variant-order-add-to-cart.plugin.js';
import BilobaArticleVariantOrderQuickviewVariantSwitch from '../biloba/article-variant-order-matrix/biloba-article-variant-order-quickview-variant-switch.plugin.js';
import BilobaArticleVariantOrderListingInlinePlugin from '../biloba/article-variant-order-matrix/biloba-article-variant-order-listing-inline.plugin.js';



//register Plugin
const PluginManager = window.PluginManager;
PluginManager.register('BilobaArticleVariantOrderListingInlinePlugin', BilobaArticleVariantOrderListingInlinePlugin,'[data-biloba-variant-matrix-listing-inline]');
PluginManager.register('BilobaArticleVariantOrderMatrix', BilobaArticleVariantOrderMatrix);
PluginManager.register('BilobaArticleVariantOrderAddToCart', BilobaArticleVariantOrderAddToCart);
PluginManager.register('BilobaArticleVariantOrderQuickviewVariantSwitch', BilobaArticleVariantOrderQuickviewVariantSwitch,'[data-biloba-variant-matrix-variant-switch]');
