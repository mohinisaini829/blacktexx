import HttpClient from 'src/service/http-client.service';
import Plugin from 'src/plugin-system/plugin.class';

export default class MyfavZweideh extends Plugin {
    /**
     * Is called, when the page is loaded.
     **/
    init() {
        // Initialize the HttpClient.
        this._client = new HttpClient();
        this._initDesignerButtons();
        this._initQuantityUpdate();
        this._initShowRequestFormTextAction();
    }

    /**
     * Init the Button "Designer starten".
     **/
    _initDesignerButtons() {
        // Only attach to this.el (the element this plugin instance is bound to)
        const input = this.el;
        input.addEventListener('keyup', (event) => {
            this._updateButtonsByPrice(this, input);
        });
        input.addEventListener('change', (event) => {
            this._updateButtonsByPrice(this, input);
        });
    }

    /**
     * Geänderte Stückzahl des einen Eingabefeldes im anderen aktualisieren.
     */
    _initQuantityUpdate() {
        // Only attach to this.el (the element this plugin instance is bound to)
        const srcElem = this.el;
        srcElem.addEventListener('keyup', (event) => {
            this._updateRequestFormQuantityField(srcElem.value);
        });
        srcElem.addEventListener('change', (event) => {
            this._updateRequestFormQuantityField(srcElem.value);
        });
    }

    /**
     * Zeigt das Anfrage-Formular, wenn man auf einen Button klickt.
     */
    _initShowRequestFormTextAction() {
        var me = this;
        let elem = document.querySelector('.myfav-show-contact-form-link');
        
        elem.addEventListener('click', function(event) {
            event.preventDefault();
            event.stopPropagation();
            me._showRequestForm(me);
            me._hideElement('.myfav-show-contact-form-link');
        });
    }

    /**
     * Stückzahl im Eingabefeld des Formulars aktualisieren
     */
    _updateRequestFormQuantityField(quantity) {
        let elem = document.querySelector('.myfav-request-quantity');
        elem.value = quantity;
    }

    /**
     * Update buttons by price.
     */
    _updateButtonsByPrice(me, input) {
        let limitOrderQty = input.getAttribute('data-attr-limit-order-qty');

        if(limitOrderQty != 1) {
            return;
        }

        let maxOrderQty = parseInt(input.getAttribute('data-attr-max-order-qty'));
        let qty = parseInt(input.value);

        if(isNaN(maxOrderQty)) {
            maxOrderQty = 0;
        }

        if(isNaN(qty)) {
            qty = 0;
        }

        if(qty <= maxOrderQty) {
            me._hideRequestFormInfo(me);
            me._showCheckoutButton(me);
        } else {
            me._hideCheckoutButton(me);
            me._showRequestFormInfo(me);
            me._showRequestForm(me);
        }
    }

    /**
     * Blende die Kaufen-Taste aus.
     */
    _hideCheckoutButton(me) {
        me._hideElement('.myfav-zweideh-buy-button');
    }

    /**
     * Zeige die Kaufen-Taste.
     */
    _showCheckoutButton(me) {
        me._showElement('.myfav-zweideh-buy-button');
    }

    _hideRequestFormInfo(me) {
        me._hideElement('.myfav-zweideh-request-form-button');
    }

    _showRequestFormInfo(me) {
        me._showElement('.myfav-zweideh-request-form-button');
    }

    _showRequestForm(me) {
        me._showElement('.myfav-zweideh-buy-contact');
    }

    /**
     * Show an element.
     **/
    _showElement(querySelector) {
        const elements = document.querySelectorAll(querySelector);

        elements.forEach(element => {
            element.style.display = 'block';
        });
    }

    /**
     * Hide an element.
     **/
    _hideElement(querySelector) {
        const elements = document.querySelectorAll(querySelector);

        elements.forEach(element => {
            element.style.display = 'none';
        });
    }

    /**
     * Append content to an element.
     **/
    _appendElement(querySelector, childElement) {
        const elements = document.querySelectorAll(querySelector);

        elements.forEach(element => {
            element.appendChild(childElement);
        });
    }

    /**
     * CSS-Klasse zu einem Element hinzufügen.
     **/
    _addClassToElement(querySelector, className) {
        const elements = document.querySelectorAll(querySelector);

        elements.forEach(element => {
            element.classList.add(className);
        });
    }
}