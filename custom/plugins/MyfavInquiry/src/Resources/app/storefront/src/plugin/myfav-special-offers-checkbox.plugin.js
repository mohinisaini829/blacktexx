import Plugin from 'src/plugin-system/plugin.class';

export default class MyfavSpecialOffersCheckboxPlugin extends Plugin {

    init() {
        this._target = this.el.closest('.special-offers-item');
        this.el.addEventListener('change', this._onChange.bind(this));
        this._onChange();
    }

    _onChange() {
        if(this.el.checked) {
            this._target.classList.remove('unchecked');
        } else {
            this._target.classList.add('unchecked');
        }
    }

}
