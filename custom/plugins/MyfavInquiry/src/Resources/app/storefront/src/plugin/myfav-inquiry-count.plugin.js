import Plugin from 'src/plugin-system/plugin.class';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import HttpClient from 'src/service/http-client.service';

export default class MyfavInquiryCountPlugin extends Plugin {

    static options = {
        target: '',
        badgeSelector: '.badge',
        badgeClassName: 'badge badge-primary header-cart-badge'
    }

    init() {
        this._client = new HttpClient();
        this.checkCount();
    }

    checkCount() {
        ElementLoadingIndicatorUtil.create(this.el);
        this._client.get(
            this.options.target,
            this.handleResponse.bind(this)
        )
    }

    handleResponse(responseJson) {
        const response = JSON.parse(responseJson);
        if(response.count > 0) {
            this.showBadge(response.count);
        }
        else {
            this.deleteBadge();
        }
        ElementLoadingIndicatorUtil.remove(this.el);
    }

    showBadge(count) {
        let badge = this.el.querySelector(this.options.badgeSelector);
        if(badge === null) {
            badge = document.createElement("p");
            badge.setAttribute('class', this.options.badgeClassName);
            this.el.appendChild(badge);
        }
        badge.textContent = count;
    }

    deleteBadge() {
        let badge = this.el.querySelector(this.options.badgeSelector);
        if(badge) {
            badge.remove();
        }
    }


}
