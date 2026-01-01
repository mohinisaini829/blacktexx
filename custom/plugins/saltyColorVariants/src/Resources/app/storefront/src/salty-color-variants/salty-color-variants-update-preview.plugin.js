import Plugin from 'src/plugin-system/plugin.class';

export default class SaltyColorVariantsUpdatePreview extends Plugin {
    init() {
        this._registerEvents();
    }

    _registerEvents() {
        this.el.addEventListener('mouseover', this._updatePreview.bind(this));
        this.el.addEventListener('mouseleave', this._resetPreview.bind(this));
        this.el.addEventListener('click', this._goto.bind(this));
    }

    _updatePreview() {
        var variantImage = this.el.querySelector('img');
        var productImage = this.el.closest('.product-box').querySelector('img.product-image');

        if(variantImage) {
            productImage.setAttribute('src', variantImage.getAttribute('src'));
            this.updateSrcSet(productImage, variantImage.getAttribute('srcset'));
        }
    }

    _resetPreview() {
        var productImage = this.el.closest('.product-box').querySelector('img.product-image');

        if(!productImage) {
            return;
        }

        if(productImage.hasAttribute('data-default-srcset')) {
            var defaultImageSrcset = productImage.getAttribute('data-default-srcset');
            this.updateSrcSet(productImage, defaultImageSrcset);
        }

        if(productImage.hasAttribute('data-default-src')) {
            var defaultImageSrc = productImage.getAttribute('data-default-src');
            productImage.setAttribute('src', defaultImageSrc);
        }
    }

    updateSrcSet(element, targetValue) {
        if(targetValue !== null && targetValue !== 'null') {
            element.setAttribute('srcset', targetValue);
        }
    }

    _goto() {
        var url = this.el.getAttribute('data-url');

        if(url) {
            window.location.href = url;
        }
    }
}
