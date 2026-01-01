'use strict';

import Plugin from 'src/plugin-system/plugin.class';
import PageLoadingIndicatorUtil from 'src/utility/loading-indicator/page-loading-indicator.util';
import DomAccess from 'src/helper/dom-access.helper';
import HttpClient from 'src/service/http-client.service';
import queryString from 'query-string';

/**
 * used in modal window quickorder to enable variant switch
 */
export default class VariantSwitchPlugin extends Plugin {
    init() {
        this._httpClient = new HttpClient();
        this._element = DomAccess.querySelector(document, '[data-biloba-variant-matrix-variant-switch]', false);

        if(this._element) {
            this._url = this._element.dataset.bilobaVariantMatrixVariantUrl;

            this._ensureFormElement();
            this._registerEvents();
        }
    }

    _update() {
        this.init();
        
        let BilobaArticleVariantOrderMatrix = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderMatrix');

        if(BilobaArticleVariantOrderMatrix.length > 0) {
            BilobaArticleVariantOrderMatrix[0]._update();
        }

        let BilobaArticleVariantOrderAddToCart = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderAddToCart');

        if(BilobaArticleVariantOrderAddToCart.length > 0) {
            BilobaArticleVariantOrderAddToCart[0]._update();
        }
    }

    /**
     * ensures that the plugin element is a form
     *
     * @private
     */
    _ensureFormElement() {
        if (this._element.nodeName.toLowerCase() !== 'form') {
            throw new Error('This plugin can only be applied on a form element!');
        }
    }

    /**
     * register all needed events
     *
     * @private
     */
    _registerEvents() {
        this._element.addEventListener('change', event => this._onChange(event));
    }

    /**
     * callback when the form has changed
     *
     * @param event
     * @private
     */
    _onChange(event) {
        const query = {
            optionChangeId: event.target.id
        };

        PageLoadingIndicatorUtil.create(this._element);

        const url = `${this._url}?'${new URLSearchParams({...query}).toString()}`;

        this._httpClient.get(`${url}`, (response) => {

            // append response to quickview
            let quickview = this._findAncestor(this._element, '.modal-body.js-pseudo-modal-template-content-element');
            
            if(quickview) {
                quickview.innerHTML = response;

                this._element = DomAccess.querySelector(quickview, 'form', false);
                this._update();
            }

            PageLoadingIndicatorUtil.remove();
        });
    }

    _findAncestor (el, sel) {
        while ((el = el.parentElement) && !((el.matches || el.matchesSelector).call(el, sel)));

        return el;
    }
}
