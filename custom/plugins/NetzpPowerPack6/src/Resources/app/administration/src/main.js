// fontawesome as JS/SVG
require('./fontawesome/all.min');

// add new category
import './module/sw-cms/component/sw-cms-sidebar';
import './module/sw-cms/component/sw-cms-section';
import './module/sw-cms/component/sw-cms-section-actions';
import './module/sw-cms/component/sw-cms-section-config';
import './module/sw-cms/component/sw-cms-block';
import './module/sw-cms/component/sw-cms-block-config';

// components
import './module/netzp-iconpicker';
import './module/netzp-cmsconfig';

// elements
import './module/sw-cms/elements/netzp-powerpack6/cta2';
import './module/sw-cms/elements/netzp-powerpack6/testimonial';
import './module/sw-cms/elements/netzp-powerpack6/alert';
import './module/sw-cms/elements/netzp-powerpack6/infobar';
import './module/sw-cms/elements/netzp-powerpack6/map';
import './module/sw-cms/elements/netzp-powerpack6/countdown';
import './module/sw-cms/elements/netzp-powerpack6/counter';
import './module/sw-cms/elements/netzp-powerpack6/parallax';
import './module/sw-cms/elements/netzp-powerpack6/tabs';
import './module/sw-cms/elements/netzp-powerpack6/collapse';
import './module/sw-cms/elements/netzp-powerpack6/card';
import './module/sw-cms/elements/netzp-powerpack6/imagecompare';
import './module/sw-cms/elements/netzp-powerpack6/html';
import './module/sw-cms/elements/netzp-powerpack6/cta';

// blocks - grids & tiles
import './module/sw-cms/blocks/netzp-powerpack6/grid/positions'; // https://github.com/shopware/platform/blob/v6.4.7.0/UPGRADE-6.4.md#position-constants-for-cms-slots
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid1';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid2';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid2l';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid2r';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid2al';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid2ar';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid3';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid3a';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid4';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid5';
import './module/sw-cms/blocks/netzp-powerpack6/grid/grid6';

// blocks - image/text
import './module/sw-cms/blocks/netzp-powerpack6/imagetext/imagetext2';
import './module/sw-cms/blocks/netzp-powerpack6/imagetext/imagetext4';

// import './module/sw-cms/blocks/netzp-powerpack6/grid/tile1';
// import './module/sw-cms/blocks/netzp-powerpack6/grid/tile2';

import './module/sw-cms/blocks/netzp-powerpack6/cta2';
import './module/sw-cms/blocks/netzp-powerpack6/alert';
import './module/sw-cms/blocks/netzp-powerpack6/infobar';
import './module/sw-cms/blocks/netzp-powerpack6/testimonial';
import './module/sw-cms/blocks/netzp-powerpack6/map';
import './module/sw-cms/blocks/netzp-powerpack6/countdown';
import './module/sw-cms/blocks/netzp-powerpack6/counter';
import './module/sw-cms/blocks/netzp-powerpack6/parallax';
import './module/sw-cms/blocks/netzp-powerpack6/tabs';
import './module/sw-cms/blocks/netzp-powerpack6/collapse';
import './module/sw-cms/blocks/netzp-powerpack6/card';
import './module/sw-cms/blocks/netzp-powerpack6/imagecompare';
import './module/sw-cms/blocks/netzp-powerpack6/cta';

import deDE from './module/sw-cms/snippet/de-DE.json';
import enGB from './module/sw-cms/snippet/en-GB.json';

Shopware.Locale.extend('de-DE', deDE);
Shopware.Locale.extend('en-GB', enGB);

