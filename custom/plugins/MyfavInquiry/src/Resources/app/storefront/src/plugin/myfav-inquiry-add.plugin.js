import Plugin from 'src/plugin-system/plugin.class';
import PseudoModalUtil from 'src/utility/modal-extension/pseudo-modal.util';
import ElementLoadingIndicatorUtil from 'src/utility/loading-indicator/element-loading-indicator.util';
import HttpClient from 'src/service/http-client.service';

export default class MyfavInquiryAddPlugin extends Plugin {

    static options = {
        target: '',
        csrf_token: '',
        modalClass: 'myfav-inquiry-modal',
    }


    init() {
        this._client = new HttpClient();
        this.el.addEventListener('click', this.onClick.bind(this));
        const buyBtn = document.querySelector('.bit-variant-button');
        if(buyBtn) {
            this._buyBtnObserver = new MutationObserver(this._onBuyBtnChange.bind(this));
            this._buyBtnObserver.observe(buyBtn, { attributes: true });
        }

    }

    onClick(event) {
        event.preventDefault();
        ElementLoadingIndicatorUtil.create(this.el);
        const formData = new FormData(this.el.form);
        formData.set('_csrf_token', this.options.csrf_token)
        this._client.post(
            this.options.target,
            formData,
            function (response) {
                ElementLoadingIndicatorUtil.remove(this.el);
                window.PluginManager.getPluginInstances('MyfavInquiryCountPlugin').forEach(p => p.checkCount());
                // open modal
                this._openModal(response);
            }.bind(this)
        )
    }

    _openModal(response) {
        const responseParsed = JSON.parse(response);
        const pseudoModal = new PseudoModalUtil(responseParsed.html);
        pseudoModal.open(this._onOpen.bind(this, pseudoModal));
    }

    _onOpen(pseudoModal) {
        window.PluginManager.initializePlugins();

        this.$emitter.publish('onOpen', { pseudoModal });

        const modal = pseudoModal.getModal();

        modal.classList.add(this.options.modalClass);
    }

    _onBuyBtnChange(mutations) {
        mutations.forEach(mutation => {
            if (mutation.attributeName === 'disabled') {
                const buyBtn = mutation.target;
                this.el.disabled = buyBtn.disabled;
            }
        });
    }
}
