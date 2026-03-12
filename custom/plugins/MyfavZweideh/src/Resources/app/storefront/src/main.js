// Dynamic imports for async plugin loading
const PluginManager = window.PluginManager;

import('./myfav-zweideh/my-add-to-cart.plugin').then(({ default: MyAddToCart }) => {
    PluginManager.override('AddToCart', MyAddToCart, '[data-add-to-cart]');
});

import('./myfav-zweideh/myfav-zweideh.plugin').then(({ default: MyfavZweideh }) => {
    PluginManager.register('MyfavZweideh', MyfavZweideh, '.myfavQtySelect');
});

import('./myfav-zweideh/myfav-zweideh-request.plugin').then(({ default: MyfavZweidehRequest }) => {
    PluginManager.register('MyfavZweidehRequest', MyfavZweidehRequest, '.myfavQtySelect');
});

// HMR support (optional)
if(module.hot) {
    module.hot.accept();
}