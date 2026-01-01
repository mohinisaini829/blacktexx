import AddToCartPlugin from 'src/plugin/add-to-cart/add-to-cart.plugin';

export default class MyAddToCartPlugin extends AddToCartPlugin {
    init() {
        super.init();
        console.log('my addToCart override');
    }
    
    /**
     * Overriden from original module.
     * 
     * On submitting the form the OffCanvas shall open, the product has to be posted
     * against the storefront api and after that the current cart template needs to
     * be fetched and shown inside the OffCanvas
     * 
     * This override extends the behaviour, to check the value of the quantity input,
     * before submitting the form, if this is a custom form for checking out designed products.
     * @param {Event} event
     * @private
     */
    _formSubmit(event) {
        event.preventDefault();
        
        let input = this._form.querySelector('.myfavQtySelect');
        console.log(input);

        // If this is not our extended form, do the default behaviour.
        if(input === null) {
            super._formSubmit(event);
            return;
        }

        // If this is our extend form, do our behaviour.
        this._hideQuantityError();

        var qty = parseInt(input.value);

        if(isNaN(qty) || qty < 1) {
            this._showQuantityError(input);
            return;
        }

        super._formSubmit(event);
    }

    _showQuantityError(input) {
        // Mark the input field as errourneous.
        input.classList.add('is-invalid');
        document.querySelector('.myfav-zweideh-error-qty-invalid').style.display = 'block';
    }

    _hideQuantityError() {
        document.querySelector('.myfavQtySelect').classList.remove('is-invalid');
        document.querySelector('.myfav-zweideh-error-qty-invalid').style.display = 'none';
        document.querySelector('.myfav-zweideh-warning-qty-max').style.display = 'none';
    }
}