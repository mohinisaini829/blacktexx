'use strict';

import Plugin from 'src/plugin-system/plugin.class.js';
import IteratorHelper from 'src/helper/iterator.helper.js';
import ButtonLoading from 'src/utility/loading-indicator/button-loading-indicator.util.js';

export default class BilobaArticleVariantOrderAddToCart extends Plugin {
    
    static options = {
        elementSelector: '.biloba-variant-matrix',
        orderButtonSelector: '.bit-variant-button',
        formSelector: '#biloba-variant-matrix-formular'
    };

    init(update = false, node=false) {
        var instanceOffvancasCart = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderAddToCart');

        if(instanceOffvancasCart.length == 1 || update) {
            var self = this;

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
        this.init(true);
    }

    _registerListingVariantMatrix(node) {
        let self = this;

        this.init(false, node);
    }

    _registerEvents(node=null) {
        var self = this;

        let selector = document
        if(node) {
            selector = node;
        }
        // fetching all buttons as a Node inside the form
        var button = selector.querySelector(self.options.orderButtonSelector);
        
        if(button) {
            // uniqueBind on button and execute Method when click event occurs
            self.uniqueBind(button, 'click', self._onAddToCart.bind(self, node));
        }
    }
    
    _onAddToCart(node=null) {

        var self = this;
        var data = {
            'redirectTo' : 'frontend.cart.offcanvas'
        };

        let selector = document
        if(node) {
            selector = node;
        }

        // fetches parent element and disables button
        let orderButton = selector.querySelector(self.options.orderButtonSelector);
        let tableForm = selector.querySelector(self.options.formSelector);

        var indicator = new ButtonLoading(orderButton);
        indicator.create();
        var url = selector.querySelector('#biloba-variant-matrix-formular').action;

        // Bug Fixed: tableForm was accesed from current obj 
        var inputFields = tableForm.getElementsByClassName('variant-matrix-quantity');
        var flag = false;

        // iterate through inputFields
        IteratorHelper.iterate(inputFields, inputField => {
            // fetching available stock
            var stock = inputField.getAttribute('data-attribute-max-stock');
            
            // checking if input value > 0 and input field isnt disabled and an input field has available stock 
            if((inputField.value > 0) && ((inputField.disabled) == false) && (stock != 0)) {
                // variant id from input field
                var id = inputField.getAttribute('data-bit-variant-id');
                // if lineItems doesnt exist in data
                if(!data.lineItems) {
                    // creating lineItems in data
                    data['lineItems'] ={};
                }
                data.lineItems[id] = {
                    'id' : id,
                    'type': 'product', 
                    'referencedId': id,
                    'stackable': 1, 
                    'removable': 1, 
                    'quantity': inputField.value
                };
                // set flag if value found
                flag = true;
            }
        });

        if(flag == true) {
            var xmlhttp = new XMLHttpRequest();

            xmlhttp.onreadystatechange = function() {
                if (xmlhttp.readyState == XMLHttpRequest.DONE) {   // XMLHttpRequest.DONE == 4
                    if (xmlhttp.status == 200) {
                        // after request send enable button
                        indicator.remove();
                        // show offcanvas
                        const offCanvasCartInstances = window.PluginManager.getPluginInstances('OffCanvasCart');
                        offCanvasCartInstances.forEach(function(element) {
                            element.openOffCanvas(window.router['frontend.cart.offcanvas'], '', () => {
                                element.$emitter.publish('openOffCanvasCart');
                            });
                        });
                    }else {
                        // after request send failed enable button
                        indicator.remove();
                    }
                }
            };
            // relative url pfad für add to cart
            xmlhttp.open('POST', url, true);
    
            // send all headers
            var header = [];
            header['Content-Type'] = 'application/json';
            
            if(header) {
                let k = null;
                for(k in header) {
                    xmlhttp.setRequestHeader(k, header[k]);
                }
            }
    
            xmlhttp.send(JSON.stringify(data));
        }
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

        var nodeId = '' + node;
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
}