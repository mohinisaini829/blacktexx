'use strict';

import Plugin from 'src/plugin-system/plugin.class';
import HttpClient from 'src/service/http-client.service';

/**
 * used in modal window quickorder to enable variant switch
 */
export default class ListingInlinePlugin extends Plugin {
    init() {
        let me = this
        
        me.url = '/biloba/article-variant-order-matrix/listingInline/' + me.el.dataset.productId
        
        me._httpClient = new HttpClient()
        
        if (me.el.classList.contains('loading')) {
            // load variant matrix
            me._loadVariantMatrix()
            me.el.classList.remove('loading');
          }          
    }

    _update() {
        this.init();
    }

    _loadVariantMatrix() {
        let me = this
        
        me._httpClient.get(me.url, (res) => {
            // add result to innerHTML
            me.el.innerHTML = res

            let BilobaArticleVariantOrderMatrix = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderMatrix');

            if(BilobaArticleVariantOrderMatrix.length > 0) {
                BilobaArticleVariantOrderMatrix[0]._registerListingVariantMatrix(me.el);
            }

            let BilobaArticleVariantOrderAddToCart = window.PluginManager.getPluginInstances('BilobaArticleVariantOrderAddToCart');

            if(BilobaArticleVariantOrderAddToCart.length > 0) {
                BilobaArticleVariantOrderAddToCart[0]._registerListingVariantMatrix(me.el);
            }
        });
    }
}
