import Plugin from 'src/plugin-system/plugin.class';

export default class SaltyColorVariantsFixLazyLoading extends Plugin {
    init() {
        this._fixLazyLoading();
    }

    _fixLazyLoading() {
        if(this.el.hasAttribute('data-src')) {
            this.el.setAttribute('src', this.el.getAttribute('data-src'));
        }

        if(this.el.hasAttribute('data-srcset')) {
            this.el.setAttribute('srcset', this.el.getAttribute('data-srcset'));
        }
    }
}
