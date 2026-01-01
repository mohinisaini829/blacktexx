const PluginManager = window.PluginManager;

PluginManager.register(
    'SolidAseContentSliderPlugin',
    () => import('./plugin/solid-advanced-slider-elements/solid-ase-content-slider.plugin'),
    '.solid-ase-content-slider'
);
