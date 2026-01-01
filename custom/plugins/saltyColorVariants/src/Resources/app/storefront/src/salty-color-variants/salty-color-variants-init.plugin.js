import Plugin from 'src/plugin-system/plugin.class';

export default class SaltyColorVariantsInit extends Plugin {
    init() {
        this._setDefaultImage();
    }

    _setDefaultImage() {
        let srcAttribute = this._getSourceAttribute('src');
        let srcsetAttribute = this._getSourceAttribute('srcset');

        this.el.setAttribute('data-default-srcset', this.el.getAttribute(srcsetAttribute));
        this.el.setAttribute('data-default-src', this.el.getAttribute(srcAttribute));
    }

    /**
     * Get the correct default attributes if lazy loading via "weedesign Optimize PageSpeed" is active
     */
    _getSourceAttribute(attribute) {
        if(false === this.el.classList.contains('weedesign-lazy-effect')) {
            return attribute;
        }

        if(true === this.el.hasAttribute('data-src')) {
            return 'data-' + attribute;
        }

        return attribute;
    }
}
