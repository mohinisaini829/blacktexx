import Plugin from 'src/plugin-system/plugin.class';

export default class PopupPlugin extends Plugin {

    init() {
        const popupId = $(this.el).data('popup-id'),
            frequencyMode = $(this.el).data('popup-show-mode');

        this.popupId = popupId;
        this.ajaxUpdateBaseUrl = $(this.el).data('popup-update-ctr-url');
        this.isRedirect = $(this.el).data('popup-is-redirect');
        this.redirectUrl = $(this.el).data('popup-redirect-url');

        localStorage.setItem('frequencyModePopup', frequencyMode);

        if ( localStorage.getItem('frequencyModePopup') === "1" && localStorage.getItem('isOnlyOnceShowPopup') === "true" ) {
            $(".htc-popup-overlay").removeClass('active').hide();
        } else if ( this.isRedirect > 0 && localStorage.getItem('isComfirmedHTCRedirectPopup') === "true" ) {
            $(".htc-popup-overlay").removeClass('active').hide();
        } else {
            setTimeout(() => {
            this._showPopup($(this.el), frequencyMode);
        }, 50000); // 5 second delay
        }
    }

    _showPopup(popup, frequencyMode) {
        let self = this; 
        $(".htc-popup-overlay").show().addClass('active');
        if ($(window).width() < 768) {
            const popupWidth = $(".htc-popup-content").outerWidth();
            let ratio = parseFloat($(this.el).data('ratio'));
            $(".htc-popup-content").css('height', popupWidth/ratio);
        }
        this._eventListener(frequencyMode);
        this._countView();
    }

    _countView() {
        this._buildAjaxRequest(this.ajaxUpdateBaseUrl, this.popupId, 1);
    }

    _countClick() {
        this._buildAjaxRequest(this.ajaxUpdateBaseUrl, this.popupId, 0);
    }

    _closePopup(frequencyMode) {
        $(".htc-popup-overlay").removeClass('active').hide();

        if (frequencyMode == 1) {
            localStorage.setItem('isOnlyOnceShowPopup', true);
        } else {
            localStorage.setItem('isOnlyOnceShowPopup', false);
        }
    }

    _buildAjaxRequest(url, popupId, mode) {
        const xhr = new XMLHttpRequest();
        const method = 'GET';
        const ajaxUpdateUrl = url + '?id='+ popupId + '&mode=' + mode;
        xhr.open(method, ajaxUpdateUrl, true);
        xhr.send();
    }

    _eventListener(frequencyMode) {
        let self = this;
        
        $(".htc-popup-confirm-button").click(function() {
            localStorage.setItem('isComfirmedHTCRedirectPopup', true);
            self._closePopup(frequencyMode);
        });

        $(".htc-popup-close").click(function() {
            if(self.isRedirect > 0) {
                window.location.href = self.redirectUrl;
                return false; 
            } 
            self._closePopup(frequencyMode);
        });

        $(".htc-popup-overlay").click(function() {
            if($(this).hasClass('active')) {
                if(self.isRedirect > 0) {
                    window.location.href = self.redirectUrl;
                    return false; 
                } 
                self._closePopup(frequencyMode);
            }
        });

        $(".htc-popup-content").click(function(event) {
            event.stopPropagation();
        });

        $(".htc-popup-content").find("a,button,.action").click(function() {
            self._countClick();
        });
    }
}
