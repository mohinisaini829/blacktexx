'use strict';

import Plugin from 'src/plugin-system/plugin.class.js';
import IteratorHelper from 'src/helper/iterator.helper.js';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';
import DomAccess from 'src/helper/dom-access.helper.js';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import HttpClient from 'src/service/http-client.service';

export default class BilobaArticleVariantOrderMatrix extends Plugin {

    static options = {
        elementSelector: '.biloba-variant-matrix',
        orderButtonSelector: '.bit-variant-button',
    };

    init(update = false, node=null) {
        var instanceOffvancasCart = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderMatrix');

        if(instanceOffvancasCart.length == 1 || update) {
            let self = this;
            self.width = window.innerWidth;

            this._httpClient = new HttpClient();
            
            let selector = document
            if(node) {
                selector = node;
            }
            let rootNode = selector.querySelector(self.options.elementSelector);
            
            if(rootNode) {
                self._registerEvents(node);
            }
        }        
    }
      
    _update() {
        // reloads modal extension after ajax reload in account area
        var el = document.querySelectorAll('.biloba-variant-button-card');
        IteratorHelper.iterate(el, element => {
            var elClone = element.cloneNode(true);
            element.parentNode.replaceChild(elClone, element);
            
        });
        
        var el = document.querySelectorAll('.biloba-variant-button-card');
        IteratorHelper.iterate(el, element => {
            element.addEventListener('click', this._onClickHandleAjaxModal.bind(this));
            element.addEventListener('touch', this._onClickHandleAjaxModal.bind(this));
        });
        
        this.init(true);
    }

    _registerListingVariantMatrix(node) {
        let self = this;

        this.init(false, node);
    }

    _onClickHandleAjaxModal(event) {
        const trigger = event.currentTarget;
        const url = DomAccess.getAttribute(trigger, 'data-url');
        
        PageLoadingIndicatorUtil.create();
        this._httpClient.get(url, response => {
            PageLoadingIndicatorUtil.remove();
            this._openModal(response);
        });
    }

    _openModal(response) {
        const pseudoModal = new PseudoModalUtil(response);
        pseudoModal.open();
        
        this._registerEvents();
        let BilobaArticleVariantOrderAddToCart = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderAddToCart');

        if(BilobaArticleVariantOrderAddToCart.length > 0) {
            BilobaArticleVariantOrderAddToCart[0]._update();
        }

        const PluginManager = window.PluginManager;

        if(BilobaArticleVariantOrderAddToCart.length == 0) {
            PluginManager.initializePlugin('BilobaArticleVariantOrderAddToCart', '[data-biloba-add-to-cart]');
        }

        let BilobaArticleVariantOrderQuickviewVariantSwitch = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderQuickviewVariantSwitch');

        if(BilobaArticleVariantOrderQuickviewVariantSwitch.length > 0) {
            BilobaArticleVariantOrderQuickviewVariantSwitch[0]._update();
        }

        if(BilobaArticleVariantOrderQuickviewVariantSwitch.length == 0) {
            PluginManager.initializePlugin('BilobaArticleVariantOrderQuickviewVariantSwitch', '[data-biloba-variant-matrix-variant-switch]');    
        }
    }

    _registerEvents(node=null) {
        let self = this;
       
        let selector = document
        if(node) {
            selector = node;
        }
        let rootNode = selector.querySelector(self.options.elementSelector);

        /* register _onButtonCondition for input fields */
        let inputFields = rootNode.querySelectorAll('input[type=number]');
        // ToDo add sleep funct, add string that is calc
        inputFields.forEach(function (inputElement){
            /* self.uniqueBind(inputElement, 'input', self._changeEventFunctions.bind(self, inputElement, node));*/
            self.uniqueBind(inputElement, 'click', self._onSelectInputValue.bind(self, inputElement));

            self.uniqueBind(inputElement, 'input', self._debounce(function(event) {
                self._changeEventFunctions(inputElement, node);
            }, 1000));
        });
            
        /* register _onButtonCondition for dropdown fields */
        let selectFields = rootNode.querySelectorAll('select');

        selectFields.forEach(selectElement => {
            self.uniqueBind(selectElement, 'change', self._onButtonCondition.bind(self, node));
            self.uniqueBind(selectElement, 'click', self._onCheckInputValue.bind(self, selectElement));
        });

        selectFields.forEach(selectElement => {
            selectElement.addEventListener("change", self._sumUpPricesWithQuantitySelect.bind(self,selectElement, node));
        });
        
        // registering _onSelect method
        let selectionBtns = rootNode.querySelectorAll('.product-detail-configurator-option button',false);

        selectionBtns.forEach(selectionBtn => {
            self.uniqueBind(selectionBtn, 'click', self._onSelect.bind(self, selectionBtn, node));
        });

        inputFields.forEach(function (inputElement){
            // registering negativ number disable method
            self.uniqueBind(inputElement, 'keyup', self._onCheckInputValueNaN.bind(self, inputElement));

            self.uniqueBind(inputElement, 'keyup', self._debounce(function(event) {
                self._onCheckInputValue(inputElement);
            }, 1000));
        });
        
        // checking if variant matrix is active in product detail
        if(window.bilobaVariantMatrixActiveIt == true) {
            /*bind so i can pass the current object of my js class and not the current object of the resize event 
            and also adding eventlistener for window resize*/
            self.uniqueBind(window, 'resize', self._onResetAmount.bind(self, false, node));
        }

        // register event for image change
        let imageChangeFields = rootNode.querySelectorAll('select[data-attribute-change-image="true"]');
        imageChangeFields.forEach(function (imageChangeField){
            self.uniqueBind(imageChangeField, 'click', self._changeImage.bind(self, imageChangeField, node));
        });

        imageChangeFields = rootNode.querySelectorAll('input[data-attribute-change-image="true"]');
        imageChangeFields.forEach(function (imageChangeField){
            //self.uniqueBind(imageChangeField, 'click', self._changeImage.bind(self, imageChangeField, node));
            self.uniqueBind(imageChangeField, 'focus', self._changeImage.bind(self, imageChangeField, node));
        });
        
        imageChangeFields = rootNode.querySelectorAll('label[data-attribute-change-image="true"]');
        imageChangeFields.forEach(function (imageChangeField){
            self.uniqueBind(imageChangeField, 'click', self._changeImage.bind(self, imageChangeField, node));
        });

        imageChangeFields = rootNode.querySelectorAll('img[data-attribute-change-image="true"]');
        imageChangeFields.forEach(function (imageChangeField){
            self.uniqueBind(imageChangeField, 'click', self._changeImage.bind(self, imageChangeField, node));
        });

        imageChangeFields = rootNode.querySelectorAll('div[data-attribute-change-image="true"]');
        imageChangeFields.forEach(function (imageChangeField){
            self.uniqueBind(imageChangeField, 'click', self._changeImage.bind(self, imageChangeField, node));
        });
    }
    /**
     * Executes button condition and the function which gives the sum for the total price
     */
    _changeEventFunctions(parentNode, node=null) {
        let self = this;

        self._onButtonCondition(node);
        self._sumUpPricesWithQuantityInput(parentNode, node);
    }

    _sumUpPricesWithQuantityInput(parentNode, node=null) {
        let self = this;
        let totalPrice = 0;
        let variantPriceValue = 0;
        let variantPrice = 0;
        
        let selector = document
        if(node) {
            selector = node;
        }
        let rootNode = selector.querySelector(self.options.elementSelector);
        let inputFields = rootNode.querySelectorAll('input[data-attribute-variant-price]');
        
        IteratorHelper.iterate(inputFields, input => {
            variantPriceValue = Number(input.getAttribute('data-attribute-variant-price'));
        });
        
        if(variantPriceValue > 0) {
            // variantPrice = variantPriceValue;

            // iterate through inputFields and check if value > 0
            IteratorHelper.iterate(inputFields, input => {
                let variantQuantity = input.value;
                variantPrice = Number(input.getAttribute('data-attribute-variant-price'));
                // price multiplied by quantity for a single variant
                let variantPriceMultBuyQuantity = variantQuantity * variantPrice;

                if(input.value != 0) {
                    totalPrice += variantPriceMultBuyQuantity;
                }
            });
        // only do the following when stack prices exist
        } else {
            let variantQuantity = parentNode.value;
            
            // iterate through array which contains the quantities, array is in input field template
            for (const [key, stackQuantity] of Object.entries(calculatedPricesQuantityArray)) {         
                if(variantQuantity <= stackQuantity || key == calculatedPricesPriceArray.length - 1) {
                    variantPrice = calculatedPricesPriceArray[key];
                    break;
                }
            }

            // setting data-attribute-variant-price value depending on input quantity
            parentNode.setAttribute('data-attribute-variant-price', variantPrice * variantQuantity);

            let inputFields = rootNode.querySelectorAll('input[data-attribute-variant-price]');

            parentNode.setAttribute('value', parentNode.value);
            
            IteratorHelper.iterate(inputFields, inputField => {
                totalPrice = Number(totalPrice) + Number(inputField.getAttribute('data-attribute-variant-price'));
                
            });
        }

        self._formatTotalPrice(totalPrice, node);
    }

    _sumUpPricesWithQuantitySelect(parentNode, node=null) {
        let self = this;
        let totalPrice = 0;
        let variantPriceValue = 0;
        let variantPrice = 0;

        let selector = document
        if(node) {
            selector = node;
        }
        let rootNode = selector.querySelector(self.options.elementSelector);

        let dropdowns = rootNode.querySelectorAll('select[data-attribute-select-price]');

        IteratorHelper.iterate(dropdowns, dropdown => {
            variantPriceValue = Number(dropdown.getAttribute('data-attribute-variant-price'));
        });

        if(variantPriceValue > 0) {
            
            // variantPrice = variantPriceValue;

            // iterate through inputFields and check if value > 0
            IteratorHelper.iterate(dropdowns, dropdown => {
                let variantQuantity = dropdown.value;
                variantPrice = Number(dropdown.getAttribute('data-attribute-variant-price'));

                // price multiplied by quantity for a single variant
                let variantPriceMultBuyQuantity = variantQuantity * variantPrice;

                if(dropdown.value != 0) {
                    totalPrice += variantPriceMultBuyQuantity;
                }
            });
        }else {
            let variantQuantity = parentNode.value;
            // fetching current selected variantPrice
            variantPrice = parentNode.options[parentNode.selectedIndex].getAttribute('data-attribute-variant-price');
            // setting data-attribute-select-price value depending on selected quantity for the selected element
            parentNode.setAttribute('data-attribute-select-price', variantPrice * variantQuantity);

            IteratorHelper.iterate(dropdowns, dropdown => {
                totalPrice = Number(totalPrice) + Number(dropdown.getAttribute('data-attribute-select-price'));
                
            });
        }

        self._formatTotalPrice(totalPrice, node);
    }

    _formatTotalPrice(totalPrice, node=null) {
        let selector = document
        
        if(node) {
            selector = node;
        }
        
        if(selector.querySelector('#variants-total-price-value') != null) {
            let htmlBody = selector.querySelector('html'); 
            let lanugageIsoCode = htmlBody.getAttribute('lang');
            let formatter = new Intl.NumberFormat(lanugageIsoCode, {minimumFractionDigits: 2 });
            // ToDo fix after comma digits
            // Reset total price after all inputs are reseted
            let valueToReplaceCommata = formatter.format(totalPrice);
            let formatedValue = '';
            //
            if(lanugageIsoCode != 'de-DE' && lanugageIsoCode == 'en-US') {
                // replace first occurence of a dot with a comma
               formatedValue = valueToReplaceCommata.toString().replace(/\./g, ',');
            }else {
                formatedValue = valueToReplaceCommata;
            }

            selector.querySelector('#variants-total-price-value').innerHTML = formatedValue;
        }
    }

    /**
     * Set button property disabled to false if input value exist
     */
    _onButtonCondition(node=null) {
        let self = this;
        // getting all inputfields

        let selector = document
        if(node) {
            selector = node;
        }
        let rootNode = selector.querySelector(self.options.elementSelector);
        let orderBtn = selector.querySelector(self.options.orderButtonSelector);

        let inputFields = rootNode.querySelectorAll('input[type=number], select');
        
        orderBtn.disabled = true;

        IteratorHelper.iterate(inputFields, input => {
            let availableStock = input.getAttribute('max');
            let checkMaxStock = input.hasAttribute('data-check-max-stock');
            
            if(checkMaxStock) {
                if((parseInt(input.value, 10) > 0) && (availableStock > 0)) {
                    orderBtn.disabled = false;
                }
            }else {
                if((parseInt(input.value, 10) > 0)) {
                    orderBtn.disabled = false;
                }
            }
        });
    }

    // select value inside input field when you click inside it
    _onSelectInputValue(element) {
        element.select();
    }

    // allows only positive whole numbers and max value = available stock
    _onCheckInputValue(element) {
        let self = this;

        // input field value
        let number = parseInt(element.value, 10);
        let checkStock = element.getAttribute('data-check-max-stock');
        // available stock
        let stock = element.getAttribute('data-attribute-max-stock');
        // if respectStock is enabled
        let respectStock = element.getAttribute('data-attribute-respect-stock');
        // get purchase Steps
        let purchaseSteps = element.getAttribute('data-attribute-purchase-steps');
        let minPurchaseSteps = element.getAttribute('data-attribute-min-purchase');
        let restToRemove = 0;

        if((respectStock == true) || (stock > 0)) {
            // if input value smaller min purchase steps
            if((number < minPurchaseSteps) && (number != 0)) {
                number = minPurchaseSteps;
            }
            // substract min purchase steps from input value and do modulo purchase steps
            if(number != 0) {
                restToRemove = (number - minPurchaseSteps) % purchaseSteps;
                /**
            *  substract rest from input value so all valid numbers have the resToRemove of 0 and number = valide number
            * for invalid numbers we use the rest of modulo and substract it from the number variable */
                number -= restToRemove;
            }
            
            // if input value < available stock 
            if(stock < number) {
                // checking if helper variable true
                if(checkStock == 'true'){
                    checkStock = 'false';
                    // setting input value to available stock
                    number = stock;
                }
            }
        }

        // Use shopwares isNan Method or you get an error
        if(isNaN(number)) number = 0;
        if(number < 0) number = 0;

        element.value = number;

        self._sumUpPricesWithQuantityInput(element);
    }

    _onCheckInputValueNaN(element) {
        // Use shopwares isNan Method or you get an error
        if(isNaN(element.value)) element.value = 0;
        if(element.value < 0) element.value = 0;
    }

    // reset value inside input fields when someone changes the viewport width
    _onResetAmount(tabUsed, node=null) {
        let self = this;
        // saving current viewport width
        let currentWidth = window.innerWidth;

        let selector = document
        if(node) {
            selector = node;
        }
        
        let rootNode = selector.querySelector(self.options.elementSelector);
        
        if(self.width != currentWidth || tabUsed == true) {
            self.width = currentWidth;
            // fetching all input and select elements
            let inputs = rootNode.querySelectorAll('input, select');

            // iterating through input elements
            inputs.forEach(function(input){
                if(input.type != 'hidden') {
                    // reseting values
                    input.value = 0;
                }
            });
            // disable button after inputfield values cleared
            let orderBtn = selector.querySelector(self.options.orderButtonSelector);
            
            if(selector.getElementById('variants-total-price-value') != null) {
                let totalPrice = 0;
                let htmlBody = selector.querySelector('html'); 
                let lanugageIsoCode = htmlBody.getAttribute('lang');
                let formatter = new Intl.NumberFormat(lanugageIsoCode, {minimumFractionDigits: 2 });
                let valueToReplaceCommata = formatter.format(totalPrice);
                let formatedValue = '';
                
                if(lanugageIsoCode != 'de-DE' && lanugageIsoCode == 'en-US') {
                    // replace first occurence of a dot with a comma
                    formatedValue = valueToReplaceCommata.toString().replace(/\./g, ',');
                }else {
                    formatedValue = valueToReplaceCommata;
                }

                selector.getElementById('variants-total-price-value').innerHTML = formatedValue;
            }

            orderBtn.disabled = true;
        }
    }

    // associating right table for selected tab
    _onSelect(element, node=null) {
        let self = this;

        let changeImage = element.getAttribute('data-attribute-change-image');
        if(changeImage == 'true') {
            self._changeImage(element, node);
        }

        let selector = document
        if(node) {
            selector = node;
        }
        let rootNode = selector.querySelector(self.options.elementSelector);
        let selectedOption = element.getAttribute('data-biloba-variant-matrix-selection-id');
        let tables = selector.querySelectorAll('[data-biloba-variant-matrix-table-id]');
        
        // select clicked button
        let selectionBtns = rootNode.querySelectorAll('.product-detail-configurator-option button',false);
        selectionBtns.forEach(selectionBtn => {
            selectionBtn.classList.remove("btn-primary");
        });
        element.classList.add("btn-primary");
        
        tables.forEach(table => {
            if(selectedOption == table.getAttribute('data-biloba-variant-matrix-table-id')) {
                table.style.display = 'table';
                this._onResetAmount(true, node);
            }
            else {
                table.style.display = 'none';
            }
        });
    }

    _changeImage(element, node=null) {
        let self = this
        let variantId = element.getAttribute('data-bit-variant-id');
        
        // get data element data-attribute-change-image-options from element
        let imageOptions = JSON.parse(element.getAttribute('data-attribute-change-image-options'));
        
        let selector = document
        let imageContainer = selector.querySelector('.product-detail-media');
        
        /* search for alternitive container */
        if(imageContainer == null) {
            imageContainer = selector.querySelector('.cms-element-image-gallery')
            
            if(imageContainer) {
                imageContainer = imageContainer.parentElement
            }
        }

        if(imageContainer == null) {
            imageContainer = selector.querySelector('.quickview-minimal-image')
        }

        if(node != null) {
            selector = node;
            try {
                if(!imageContainer) {
                    imageContainer = selector.closest('.card').querySelector('.product-image-wrapper a');
                }
            }
            catch(e) {
            }
        }
        
        
        self._httpClient.get(imageOptions.url, (res) => {
            imageContainer.innerHTML = res;

            // update gallery slider plugin for image
            PluginManager.initializePlugin('GallerySlider', '[data-gallery-slider]');
        });
    }      

    /**
     * Makes shure that for every node every event can only hold one event listener. If a event listener
     * was bind before it will be removed.
     * 
     * @param {object} node 
     * @param {string} event 
     * @param {function} cb 
     */
    uniqueBind(node, event, cb) {
        function uniqueId() {
            return '_' + Math.random().toString(36).substr(2, 9);
        }

        let nodeId = '' + node;
        if(node.getAttribute) {
            nodeId = node.getAttribute('id');
            if(!nodeId) {
                nodeId = uniqueId();
                node.setAttribute('id', nodeId);
            }
        }

        this.listeners = this.listeners || {};
        this.listeners[nodeId] = this.listeners[nodeId] || {};

        if(this.listeners[nodeId][event]){
            node.removeEventListener(event, this.listeners[nodeId][event]);
        }
    
        this.listeners[nodeId][event] = cb;
        node.addEventListener(event, cb);
    }

    _debounce(func, wait, immediate) {
        var timeout;
        return function() {
            var context = this, args = arguments;
            var later = function() {
                timeout = null;
                if (!immediate) func.apply(context, args);
            };
            var callNow = immediate && !timeout;
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
            if (callNow) func.apply(context, args);
        };
    };
}