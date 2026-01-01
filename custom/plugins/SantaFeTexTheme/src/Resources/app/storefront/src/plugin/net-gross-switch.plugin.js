import Plugin from 'src/plugin-system/plugin.class';

export default class NetGrossSwitchPlugin extends Plugin {

    init() {
        this.input = this.el.querySelector('input');
        this.input.addEventListener('change', this._onChange.bind(this));
        this.container = document.querySelector('.product-detail-price-container');
        this._onChange();
    }

    _onChange() {
        if(this.container === null)
            return;
        if(this.input.checked) {
            this.container.classList.remove('show-net');
        }
        else {
            this.container.classList.add('show-net');
        }
    }
}
