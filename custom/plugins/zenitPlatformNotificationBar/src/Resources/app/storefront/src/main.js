const PluginManager = window.PluginManager;
PluginManager.register('zenitNotificationBar', () => import ('./script/notification-bar.plugin'), '[data-zenit-notification-bar]');
PluginManager.register('zenitNotificationBarSlider', () => import ('./script/notification-bar-slider.plugin'), '[data-zenit-notification-bar-slider="true"]');

// Necessary for the webpack hot module reloading server
if (module.hot) {
    module.hot.accept();
}
