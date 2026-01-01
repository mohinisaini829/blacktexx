window.PluginManager.register(
    'BlogSlider',
    () => import('./plugin/blog-assignment/blog-slider.plugin'),
    '[data-blog-slider]'
);
