import './module/sw-cms/blocks/advanced-slider-elements/content-slider';
import './module/sw-cms/elements/advanced-slider-elements/content-slider';

import deDE from './module/sw-cms/snippet/de-DE.json';
import enGB from './module/sw-cms/snippet/en-GB.json';

const { Locale } = Shopware;

Locale.extend('en-GB', enGB);
Locale.extend('de-DE', deDE);
