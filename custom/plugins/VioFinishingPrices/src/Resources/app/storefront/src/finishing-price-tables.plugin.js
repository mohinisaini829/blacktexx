import Plugin from 'src/plugin-system/plugin.class';
import DomAccess from 'src/helper/dom-access.helper';

export default class FinishingPriceTablesPlugin extends Plugin {
    init() {
        this.$select = DomAccess.querySelector(this.el, '.finishing-price-tables-select');
        this.$select.addEventListener('change', this.onChange.bind(this));
    }

    onChange() {
        const id = this.$select.value,
            selector = '#finishing-price-table-' + id,
            textNode = DomAccess.querySelector(this.el, selector)
        ;
        document.querySelectorAll('.finishing-price-table-text.is-active').forEach(node => {
            node.classList.remove('is-active');
        });
        if(textNode !== null) {
            textNode.classList.add('is-active');
        }
    }
}
