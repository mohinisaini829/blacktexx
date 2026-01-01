import GallerySliderPlugin from 'src/plugin/slider/gallery-slider.plugin';

export default class MultipleItemsGallerySliderPlugin extends GallerySliderPlugin {
    init() {
        this.$emitter.subscribe('initThumbnailSlider', this.onInitThumbnailSliderFixNav.bind(this));
        super.init();
    }

    onInitThumbnailSliderFixNav() {
        const items = this.options.slider.items;
        const navContainer = this.el.querySelector(this.options.thumbnailsSelector);
        if(typeof items !== 'undefined' && items > 1 && navContainer) {
            navContainer.querySelectorAll('[data-nav]').forEach((el) => {
                el.dataset.nav = el.dataset.nav / items;
            });
        }
    }
}
