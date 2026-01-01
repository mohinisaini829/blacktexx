window.PluginManager.register('NetzpPowerpack6Countdown', () => import('./netzp-powerpack6/countdown/netzp-powerpack-countdown.plugin'), '[data-netzp-powerpack6-countdown]');
window.PluginManager.register('NetzpPowerpack6Counter', () => import('./netzp-powerpack6/counter/netzp-powerpack-counter.plugin'), '[data-netzp-powerpack6-counter]');
window.PluginManager.register('NetzpPowerpack6Imagecompare', () => import('./netzp-powerpack6/imagecompare/netzp-powerpack-imagecompare.plugin'), '[data-netzp-powerpack6-imagecompare]');
window.PluginManager.register('NetzpPowerpack6Map', () => import('./netzp-powerpack6/map/netzp-powerpack-map.plugin'), '[data-netzp-powerpack6-map]');

if (module.hot) {
    module.hot.accept();
}
