import localeDE from './snippet/de_DE.json';
import localeEN from './snippet/en_GB.json';
import './module/myfav-inquiry';
import './module/sw-cms/elements/myfav-special-offers';
//import './module/sw-cms/elements/myfav-inquiry-element';
import './module/sw-cms/elements/myfav-inquiry-element/config';
import './module/sw-cms/elements/myfav-inquiry-element/component';

import './extension/sw-flow-sequence-action';
import './component/myfav-inquiry-modal';

Shopware.Locale.extend('de-DE', localeDE);
Shopware.Locale.extend('en-GB', localeEN);