import './config';

const cmsService = Shopware.Service('cmsService');

let baseConfig = cmsService.getCmsElementConfigByName('product-slider');

// create new object to avoid overwriting the original object
let cmsElementConfig = Object.assign({}, baseConfig);
cmsElementConfig.name = 'myfav-special-offers';
cmsElementConfig.label = 'sw-cms.elements.myfav-special-offers.label';
cmsElementConfig.configComponent = 'sw-cms-el-config-myfav-special-offers';

cmsService.registerCmsElement(cmsElementConfig);
