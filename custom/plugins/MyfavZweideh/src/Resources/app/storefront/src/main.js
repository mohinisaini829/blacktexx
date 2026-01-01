import MyfavZweideh from './myfav-zweideh/myfav-zweideh.plugin';
import MyAddToCart from './myfav-zweideh/my-add-to-cart.plugin';
import MyfavZweidehRequest from './myfav-zweideh/myfav-zweideh-request.plugin';

// Register the plugin via the PluginManager
const PluginManager = window.PluginManager;

PluginManager.override('AddToCart', MyAddToCart, '[data-add-to-cart]');

if(module.hot) {
    module.hot.accept();
}

PluginManager.register('MyfavZweideh', MyfavZweideh, '.myfavQtySelect');
PluginManager.register('MyfavZweidehRequest', MyfavZweidehRequest, '.myfavQtySelect');