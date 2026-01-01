import PopupPlugin from './plugin/htc-popup/popup.plugin';

const PluginManager = window.PluginManager;
PluginManager.register('PopupPlugin', PopupPlugin, '[data-htc-popup]');
