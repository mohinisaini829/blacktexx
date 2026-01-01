import HttpClient from 'src/service/http-client.service';
import Iterator from 'src/helper/iterator.helper';
import DomAccess from 'src/helper/dom-access.helper';
import CookieStorage from 'src/helper/storage/cookie-storage.helper';
import DeviceDetection from 'src/helper/device-detection.helper';

import { COOKIE_CONFIGURATION_UPDATE } from 'src/plugin/cookie/cookie-configuration.plugin';

export default class AcrisCookieConsentPlugin extends window.PluginBaseClass {

    static options = {
        cookiePermissionAcceptButtonSelector: '#ccAcceptButton',

        cookiePermissionAcceptOnlyFunctionalButtonSelector: '#ccAcceptOnlyFunctional',

        cookiePermissionAcceptAllButtonSelector: '#ccConsentAcceptAllButton',

        cookiePermissionActivateModal: '#ccAcivateModal',

        cookiePermissionSettingsSelector: '#ccSettingButton',

        acceptOnlyFunctionalCookiesUrl: '',

        acceptAllCookiesUrl: '',

        acceptCookieSettingsUrl: '',

        acceptCookieUrl: '',

        cookieGroupCheckboxSelector: '.cookie-setting--switch--group',

        cookieCheckboxSelector: '.cookie-setting--switch--group[data-cookieid]',

        acrisAllowCookie: 'acris_cookie_acc',

        disabledClass: 'is-disabled',

        hasAcceptedClass: 'has--accepted',

        cookiePreference: 'cookie-preference',

        notFunctionalSelector: '.is--not-functional',

        submitEvent: (DeviceDetection.isTouchDevice()) ? 'touchstart' : 'click',

        customLinkSelector: `[href="${window.router['frontend.cookie.offcanvas']}"]`,

        pageReload: false,

        dataLayerCookieNamePrefix: 'acrisCookie.',

        dataLayerCookieIdPrefix: 'acrisCookieUniqueId.',

        dataLayerCookieNameFirstActivatedPrefix: 'acrisCookie.firstActivated.',

        dataLayerCookieIdFirstActivatedPrefix: 'acrisCookieUniqueId.firstActivated.',

        dataLayerCookieStateEventName: 'acrisCookieStateChanged',

        cookieNameLandingPage: 'acris_cookie_landing_page',

        cookieNameLandingReferrer: 'acris_cookie_referrer',

        dontAddToDataLayer: false,

        cookieNameActivatedCookies: 'acris_cookie_first_activated',

        cookieCheckContentSelector: '[data-acriscookie="true"]',

        noCookieSetInfoClass: 'acris-cookie-info',

        showAcceptBtnAfterSettingsClick: false
    };

    init() {
        this.lastState = {
            active: [],
            inactive: []
        };

        this._loadContentCookies = [];
        this._client = new HttpClient(window.accessKey, window.contextToken);
        this._collectComponents();
        this.lastState = this.getCurrentCookieState(true);
        this._registerEvents();
        this._openModal();
        this.saveReferrerAndLandingPage();
        this.addCookieStateToDataLayer();
        this.loadContent();
        this.handleCookieChangeEvent();
        this.comingFromOwnUpdateEvent = false;
        this.settingsButtonClicked = false;
        this.modalOpened = false;
    }

    /**
     * collect all needed components
     *
     * @private
     */
    _collectComponents() {
        this.acceptButton = DomAccess.querySelector(this.el, this.options.cookiePermissionAcceptButtonSelector, false);
        this.settingsButton = DomAccess.querySelector(this.el, this.options.cookiePermissionSettingsSelector, false);

        this.acceptOnlyFunctionalButton = DomAccess.querySelector(this.el, this.options.cookiePermissionAcceptOnlyFunctionalButtonSelector, false);
        this.acceptAllButton = DomAccess.querySelector(this.el, this.options.cookiePermissionAcceptAllButtonSelector, false);

        this.cookieGroupCheckboxes = DomAccess.querySelectorAll(this.el, this.options.cookieGroupCheckboxSelector, false);

        this.cookieCheckboxes = DomAccess.querySelectorAll(this.el, this.options.cookieCheckboxSelector, false);
    }

    getCurrentCookieState(initial) {
        const cookiesAccepted = this.checkCookiesAccepted();
        const activeCookieNames = [];
        const inactiveCookieNames = [];

        if(!this.cookieCheckboxes) {
            return;
        }

        Iterator.iterate(this.cookieCheckboxes, (cookieCheckbox) => {
            if(cookieCheckbox.dataset.cookiename) {
                if(initial === true && !cookiesAccepted) {
                    inactiveCookieNames.push(cookieCheckbox.dataset.cookiename);
                } else {
                    if(cookieCheckbox.checked) {
                        activeCookieNames.push(cookieCheckbox.dataset.cookiename);
                    } else {
                        inactiveCookieNames.push(cookieCheckbox.dataset.cookiename);
                    }
                }
            }
        });

        return {
            active: activeCookieNames,
            inactive: inactiveCookieNames
        };
    }

    /**
     * registers all needed event listeners
     *
     * @private
     */
    _registerEvents() {
        if(this.acceptButton) this.acceptButton.addEventListener('click', this.onAcceptCookieSettings.bind(this));
        if(this.acceptOnlyFunctionalButton) this.acceptOnlyFunctionalButton.addEventListener('click', this.onAcceptOnlyFunctionalCookies.bind(this));
        if(this.acceptAllButton) this.acceptAllButton.addEventListener('click', this.onAcceptAllCookies.bind(this));
        if(this.options.showAcceptBtnAfterSettingsClick) this.settingsButton.addEventListener('click', this.onOpenCookieSettingsClick.bind(this));

        // Add event listener for the settings button to set focus on the first button after expansion
        if(this.settingsButton) {
            this.settingsButton.addEventListener('click', this.onSettingsButtonClick.bind(this));
            const ccSettings = document.getElementById('ccSettings');
            if(ccSettings) {
                ccSettings.addEventListener('shown.bs.collapse', this.onSettingsShown.bind(this));
            }
        }

        // Add event listener for the modal to set focus on the first button when it's shown
        const modal = document.getElementById('ccAcivateModal');
        if(modal) {
            modal.addEventListener('shown.bs.modal', this.onModalShown.bind(this));
        }

        if(this.cookieGroupCheckboxes) {
            Iterator.iterate(this.cookieGroupCheckboxes, (cookieGroupCheckBox) => {
                cookieGroupCheckBox.addEventListener('change', this.onChangeCookieGroupCheckbox.bind(this));
                // Add event listener for Enter key to toggle the switch
                cookieGroupCheckBox.addEventListener('keydown', this.onCookieSwitchKeydown.bind(this));
            });
        }

        // Add event listeners for the individual cookie checkboxes
        if(this.cookieCheckboxes) {
            Iterator.iterate(this.cookieCheckboxes, (cookieCheckbox) => {
                // Add event listener for Enter key to toggle the switch
                cookieCheckbox.addEventListener('keydown', this.onCookieSwitchKeydown.bind(this));
            });
        }

        // Add event listeners for elements with data-groupidcookie attribute
        const cookieGroupIdCheckboxes = DomAccess.querySelectorAll(this.el, '*[data-groupidcookie]', false);
        if(cookieGroupIdCheckboxes) {
            Iterator.iterate(cookieGroupIdCheckboxes, (cookieGroupIdCheckbox) => {
                // Add event listener for Enter key to toggle the switch
                cookieGroupIdCheckbox.addEventListener('keydown', this.onCookieSwitchKeydown.bind(this));
            });
        }

        /* from Shopware cookie plugin */
        const { submitEvent, customLinkSelector } = this.options;

        Array.from(document.querySelectorAll(customLinkSelector)).forEach(customLink => {
            customLink.addEventListener(submitEvent, this._handleCustomLink.bind(this));
        });
    }

    /**
     * opens the cookie modal window
     *
     * @private
     */
    _openModal() {
        const cookiesAccepted = this.checkCookiesAccepted();
        this.modal = DomAccess.querySelector(this.el, this.options.cookiePermissionActivateModal, false);
        this.cookieAccepted = DomAccess.querySelector(document, '.acris-cookie-consent.has--accepted', false);
        if(this.modal && !this.cookieAccepted && !cookiesAccepted) {
            document.getElementById("ccActivateModalLink").click();
        }
    }

    handleCookieChangeEvent() {
        document.$emitter.subscribe(COOKIE_CONFIGURATION_UPDATE, this.handleCookies.bind(this));
    }

    handleCookies(cookieUpdateEvent) {
        if(this.comingFromOwnUpdateEvent === true) {
            this.comingFromOwnUpdateEvent = false;
            return;
        }

        const updatedCookies = cookieUpdateEvent.detail;

        if(!updatedCookies instanceof Object) return;

        Object.entries(updatedCookies).forEach(([cookieName, value]) => {
            if(cookieName) {
                this.searchAndCheckCookieCheckbox(cookieName, value);
            }
        });
    }

    onOpenCookieSettingsClick() {
        if (this.acceptButton.classList.contains('d-none')) {
            this.acceptButton.classList.remove('d-none');
            this.acceptButton.removeAttribute("disabled");
            this.settingsButton.removeEventListener('click', this.onOpenCookieSettingsClick.bind(this));
        }
    }

    onAcceptCookieSettings() {
        CookieStorage.setItem(this.options.cookiePreference, '1', '30');
        this.checkCookieBoxes(null, true);
        this.hideCookieNoteContainer();
        this.addCookieStateToDataLayer(true);
        this.loadContent();

        this._client.post(this.options.acceptCookieSettingsUrl, JSON.stringify({'accept': true}), this.onAcceptCookies.bind(this));
    }

    onAcceptOnlyFunctionalCookies() {
        CookieStorage.setItem(this.options.cookiePreference, '1', '30');
        this.changeNotFunctionalCookiesAndGroups(false);
        this.checkCookieBoxes(null, true);
        this.hideCookieNoteContainer();
        this.addCookieStateToDataLayer();
        this.loadContent();

        this._client.post(this.options.acceptOnlyFunctionalCookiesUrl, '', this.fireUpdateCookiesEvent.bind(this));
    }

    onAcceptAllCookies(){
        CookieStorage.setItem(this.options.acrisAllowCookie, '1', '30');
        CookieStorage.setItem(this.options.cookiePreference, '1', '30');
        this.changeNotFunctionalCookiesAndGroups(true);
        this.checkCookieBoxes(null, true);
        this.hideCookieNoteContainer();
        this.addCookieStateToDataLayer(true);
        this.loadContent();

        this._client.post(this.options.acceptAllCookiesUrl, '', this.onAcceptCookies.bind(this));
    }

    onAcceptCookies() {
        if(this.options.pageReload) {
            location.reload();
        }
        this.fireUpdateCookiesEvent();
    }

    onChangeCookieGroupCheckbox(event) {
        var target = event.target,
            enabled = target.checked,
            groupId = target.dataset.groupid,
            cookieId = target.dataset.cookieid,
            cookieName = target.dataset.cookiename;


        if(groupId) {
            if(enabled) {
                target.ariaChecked = true;
            }else{
                target.ariaChecked = false;
            }
            this.switchAllCookies(target, groupId, enabled);
        }
        if(cookieName) {
            if(enabled) {
                target.ariaChecked = true;
            }else{
                target.ariaChecked = false;
            }
            this.checkCookieBoxes(target);
        }
        this.addCookieStateToDataLayer();

        this._client.post(this.options.acceptCookieUrl, JSON.stringify({
            'allow': enabled,
            'groupId': groupId,
            'cookieId': cookieId
        }), this.fireUpdateCookiesEvent.bind(this, true));
    }

    switchAllCookies(groupEl, groupId, enabled) {
        var cookieCheckboxes = DomAccess.querySelectorAll(this.el, '*[data-groupidcookie="'+groupId+'"]', false);
        this.switchCookieCheckboxes(cookieCheckboxes, enabled);
    }

    changeNotFunctionalCookiesAndGroups (activate){
        var checkboxes = DomAccess.querySelectorAll(this.el, this.options.notFunctionalSelector + " " + this.options.cookieGroupCheckboxSelector, false);
        this.switchCookieCheckboxes(checkboxes, activate);
    }

    switchCookieCheckboxes(cookieCheckboxes, activate) {
        if(cookieCheckboxes !== false) {
            Iterator.iterate(cookieCheckboxes, (cookieCheckbox) => {
                this.switchSingleCookieCheckbox(cookieCheckbox, activate);
            });
            this.checkCookieBoxes(cookieCheckboxes);
        }
    }

    switchSingleCookieCheckbox(cookieCheckbox, activate) {
        if(activate) {
            cookieCheckbox.checked = true;
            cookieCheckbox.ariaChecked = true;
            cookieCheckbox.disabled = false;
            cookieCheckbox.ariaReadOnly = false;
        } else {
            cookieCheckbox.checked = false;
            cookieCheckbox.ariaChecked = false;
            if(cookieCheckbox.dataset.groupidcookie) {
                cookieCheckbox.disabled = "disabled";
                cookieCheckbox.ariaReadOnly = true;
            }
        }
    }

    switchSingleGroupByCookieCheckbox(cookieCheckbox, activate) {
        if(this.cookieGroupCheckboxes) {
            Iterator.iterate(this.cookieGroupCheckboxes, (cookieGroupCheckBox) => {
                if(cookieCheckbox.dataset.groupidcookie && cookieCheckbox.dataset.groupidcookie === cookieGroupCheckBox.dataset.groupid) {
                    if(activate) {
                        cookieGroupCheckBox.checked = true;
                        cookieGroupCheckBox.ariaChecked = true;
                    } else {
                        cookieGroupCheckBox.checked = false;
                        cookieGroupCheckBox.ariaChecked = false;
                    }
                }
            });
        }
    }

    checkCookieBoxes(cookieCheckboxes, force) {
        if(cookieCheckboxes === undefined || cookieCheckboxes === false) cookieCheckboxes = null;
        if(force === undefined) force = false;
        if(force === false && !this.checkCookiesAccepted()) return;

        if(cookieCheckboxes === null) cookieCheckboxes = this.cookieCheckboxes;

        if(cookieCheckboxes) {
            Iterator.iterate(cookieCheckboxes, (cookieCheckbox) => {
                this.enableCookie(cookieCheckbox);
            });
        }
    }

    enableCookie(cookieCheckbox) {
        if(cookieCheckbox.dataset.cookiename) {
            if(cookieCheckbox.dataset.cookievalue) {
                this.enableCookieByNameAndValue(cookieCheckbox.dataset.cookiename, cookieCheckbox.dataset.cookievalue, cookieCheckbox.checked);
            }
            this.enableCookieByName(cookieCheckbox.dataset.cookiename, cookieCheckbox.checked);
        }
    }

    searchAndCheckCookieCheckbox(cookieName, value) {
        if(!this.cookieCheckboxes instanceof Object) return;
        Iterator.iterate(this.cookieCheckboxes, (cookieCheckbox) => {
            if (cookieCheckbox.dataset.cookiename === cookieName && cookieCheckbox.dataset.cookieid) {
                let payload = {'allow': value, 'cookieId': cookieCheckbox.dataset.cookieid};
                if(value) {
                    this.switchSingleGroupByCookieCheckbox(cookieCheckbox, value);
                    // set payload value to accept true, to accept if not accepted yet
                    payload = {'accept': true, 'allow': value, 'cookieId': cookieCheckbox.dataset.cookieid, 'groupId': cookieCheckbox.dataset.groupidcookie};
                }
                this.switchSingleCookieCheckbox(cookieCheckbox, value);
                CookieStorage.setItem(this.options.cookiePreference, '1', '30');
                this.enableCookie(cookieCheckbox);
                this._client.post(this.options.acceptCookieUrl, JSON.stringify(payload));
            }
        });
    }

    checkCookiesAccepted() {
        return CookieStorage.getItem(this.options.acrisAllowCookie);
    }

    enableCookieByNameAndValue(name, value, enabled) {
        if(window.acrisCookiePrivacy) {
            if (enabled) {
                const date = new Date();
                date.setTime(date.getTime() + (30 * 24 * 60 * 60 * 1000));
                window.acrisCookiePrivacy.remeberCookieValue(name, `${name}=${value};expires=${date.toUTCString()};path=/`);
            } else {
                window.acrisCookiePrivacy.denyCookieByName(name);
            }
        }
    }

    enableCookieByName(name, enabled) {
        if(window.acrisCookiePrivacy) {
            if(enabled) {
                window.acrisCookiePrivacy.allowCookieByName(name);
            } else {
                window.acrisCookiePrivacy.denyCookieByName(name);
            }
        }
    }

    fireUpdateCookiesEvent(needToCheckIfAccepted) {
        if(needToCheckIfAccepted === true && !this.checkCookiesAccepted()) return;
        const currentState = this.getCurrentCookieState(false);
        const updatedCookies = this._getUpdatedCookies(currentState);
        this.lastState = currentState;
        this.comingFromOwnUpdateEvent = true;
        document.$emitter.publish(COOKIE_CONFIGURATION_UPDATE, updatedCookies, true);
    }

    hideCookieNoteContainer() {
        this.el.classList.add(this.options.hasAcceptedClass);
    }

    /**
     * Prevent the event default e.g. for anchor elements using the href-selector
     *
     * @param event
     * @private
     */
    _handleCustomLink(event) {
        event.preventDefault();

        window.openCookieConsentManager();
    }

    /**
     * Shopware cookie consent: Compare the current in-/active cookies to the initialState and return updated cookies only
     *
     * @param currentState
     * @private
     */
    _getUpdatedCookies(currentState) {
        const { lastState } = this;
        const updated = {};

        if(currentState && currentState.active) {
            currentState.active.forEach(currentCheckbox => {
                if (lastState.inactive.includes(currentCheckbox) === true) {
                    updated[currentCheckbox] = true;
                }
            });
        }

        if(currentState && currentState.inactive) {
            currentState.inactive.forEach(currentCheckbox => {
                if (lastState.active.includes(currentCheckbox)) {
                    updated[currentCheckbox] = false;
                }
            });
        }

        return updated;
    }

    addCookieStateToDataLayer(accepted) {
        let cookies = [],
            checked,
            firstActivated = false,
            cookiesAccepted = this.checkCookiesAccepted() || accepted,
            ad_storage = false,
            ad_user_data = false,
            ad_personalization = false,
            analytics_storage = false,
            functionality_storage = false,
            personalization_storage = false,
            security_storage = false;
        window.dataLayer = window.dataLayer || [];
        window._mtm = window._mtm || [];

        if(this.options.dontAddToDataLayer) {
            return;
        }

        if(!this.cookieCheckboxes) {
            return;
        }



        Iterator.iterate(this.cookieCheckboxes, (cookieCheckbox) => {
            if(cookieCheckbox.dataset.cookiename) {
                if(!cookiesAccepted) {
                    checked = false;
                } else {
                    checked = cookieCheckbox.checked;
                }

                if(checked === true) {
                    firstActivated = this.checkFirstActivated(cookieCheckbox.dataset.cookieid);
                }

                cookies.push({
                    cookieId: cookieCheckbox.dataset.cookiename,
                    enabled: checked,
                    uniqueId: cookieCheckbox.dataset.cookieid,
                    name: cookieCheckbox.dataset.cookietitle,
                    firstActivated: firstActivated,
                    ad_storage: cookieCheckbox.dataset.cookiegoogleconsentmodeadstorage,
                    ad_user_data: cookieCheckbox.dataset.cookiegoogleconsentmodeaduserdata,
                    ad_personalization: cookieCheckbox.dataset.cookiegoogleconsentmodeadpersonalization,
                    analytics_storage: cookieCheckbox.dataset.cookiegoogleconsentmodeanalyticsstorage,
                    functionality_storage: cookieCheckbox.dataset.cookiegoogleconsentmodefunctionalitystorage,
                    personalization_storage: cookieCheckbox.dataset.cookiegoogleconsentmodepersonalizationstorage,
                    security_storage: cookieCheckbox.dataset.cookiegoogleconsentmodesecuritystorage
                });

                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + cookieCheckbox.dataset.cookiename, checked);
                this.addToDataLayer(this.options.dataLayerCookieIdPrefix + cookieCheckbox.dataset.cookieid, checked);
                if(cookieCheckbox.dataset.cookiegoogleconsentmodeadstorage) {
                    if(checked === true)
                        ad_storage = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodeaduserdata) {
                    if(checked === true)
                        ad_user_data = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodeadpersonalization) {
                    if(checked === true)
                        ad_personalization = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodeanalyticsstorage) {
                    if(checked === true)
                        analytics_storage = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodefunctionalitystorage) {
                    if(checked === true)
                        functionality_storage = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodepersonalizationstorage) {
                    if(checked === true)
                        personalization_storage = true;
                }
                if(cookieCheckbox.dataset.cookiegoogleconsentmodesecuritystorage) {
                    if(checked === true)
                        security_storage = true;
                }

                if(firstActivated === true) {
                    this.addToDataLayer(this.options.dataLayerCookieNameFirstActivatedPrefix + cookieCheckbox.dataset.cookiename, true);
                    this.addToDataLayer(this.options.dataLayerCookieIdFirstActivatedPrefix + cookieCheckbox.dataset.cookieid, true);
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodeadstorage) {
                        if(checked === true)
                            ad_storage = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodeaduserdata) {
                        if(checked === true)
                            ad_user_data = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodeadpersonalization) {
                        if(checked === true)
                            ad_personalization = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodeanalyticsstorage) {
                        if(checked === true)
                            analytics_storage = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodefunctionalitystorage) {
                        if(checked === true)
                            functionality_storage = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodepersonalizationstorage) {
                        if(checked === true)
                            personalization_storage = true;
                    }
                    if(cookieCheckbox.dataset.cookiegoogleconsentmodesecuritystorage) {
                        if(checked === true)
                            security_storage = true;
                    }
                }
            }
        });

        if(cookiesAccepted) {
            if (ad_storage) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_storage", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_storage", "denied");
            }
            if (ad_user_data) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_user_data", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_user_data", "denied");
            }
            if (ad_personalization) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_personalization", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "ad_personalization", "denied");
            }
            if (analytics_storage) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "analytics_storage", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "analytics_storage", "denied");
            }
            if (functionality_storage) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "functionality_storage", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "functionality_storage", "denied");
            }
            if (personalization_storage) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "personalization_storage", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "personalization_storage", "denied");
            }
            if (security_storage) {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "security_storage", "granted");
            } else {
                this.addToDataLayer(this.options.dataLayerCookieNamePrefix + "security_storage", "denied");
            }
        }

        window.dataLayer.push({
            'acrisCookieState': cookies
        });
        window._mtm.push({
            'acrisCookieState': cookies
        });

        window.dataLayer.push({'event': this.options.dataLayerCookieStateEventName});
        window._mtm.push({'event': this.options.dataLayerCookieStateEventName});
    }

    addToDataLayer(name, value) {
        let singleDataLayerCookie = {};
        singleDataLayerCookie[name] = value;
        window.dataLayer.push(singleDataLayerCookie);
        window._mtm.push(singleDataLayerCookie);
    }

    saveReferrerAndLandingPage() {
        let landingPageUrl = CookieStorage.getItem(this.options.cookieNameLandingPage);
        window.dataLayer = window.dataLayer || [];
        window._mtm = window._mtm || [];

        if(!landingPageUrl) {
            document.cookie = this.options.cookieNameLandingPage + "=" + window.location.pathname + window.location.search + ";path=/;SameSite=Lax";
            document.cookie = this.options.cookieNameLandingReferrer + "=" + document.referrer + ";path=/;SameSite=Lax";
        }

        window.dataLayer.push({
            'acrisCookieLandingpage': CookieStorage.getItem(this.options.cookieNameLandingPage),
            'acrisCookieReferrer': CookieStorage.getItem(this.options.cookieNameLandingReferrer)
        });
        window._mtm.push({
            'acrisCookieLandingpage': CookieStorage.getItem(this.options.cookieNameLandingPage),
            'acrisCookieReferrer': CookieStorage.getItem(this.options.cookieNameLandingReferrer)
        });
    }

    checkFirstActivated(cookieId) {
        var cookieIds = [],
            activatedCookiesString = CookieStorage.getItem(this.options.cookieNameActivatedCookies);
        if(activatedCookiesString) {
            cookieIds = activatedCookiesString.split("|");
            if(cookieIds.includes(String(cookieId))) {
                return false;
            }
        }
        cookieIds.push(cookieId);
        activatedCookiesString = cookieIds.join("|");
        document.cookie = this.options.cookieNameActivatedCookies + "=" + activatedCookiesString + ";path=/;SameSite=Lax";
        return true;
    }

    /**
     * Sets the flag when the settings button is clicked
     */
    onSettingsButtonClick() {
        this.settingsButtonClicked = true;
    }

    /**
     * Sets focus on the first button element in the settings container
     * after the settings are expanded, but only if the settings button was clicked
     */
    onSettingsShown() {
        if (this.settingsButtonClicked) {
            const ccSettings = document.getElementById('ccSettings');
            if (ccSettings) {
                // Find the first button element in the settings container
                const firstButton = ccSettings.querySelector('button');
                if (firstButton) {
                    firstButton.focus();
                }
            }
            // Reset the flag
            this.settingsButtonClicked = false;
        }
    }

    /**
     * Handle keydown event on cookie switches
     * Toggle the switch when Enter key is pressed
     * 
     * @param {Event} event - The keydown event
     */
    onCookieSwitchKeydown(event) {
        // Check if Enter key was pressed
        if (event.key === 'Enter') {
            // Prevent default action
            event.preventDefault();

            // Toggle the checkbox
            const checkbox = event.target;
            checkbox.checked = !checkbox.checked;

            // Trigger the change event to ensure the same functionality as clicking
            const changeEvent = new Event('change', { bubbles: true });
            checkbox.dispatchEvent(changeEvent);
        }
    }

    /**
     * Sets focus on the first button element in the modal
     * after the modal is shown
     */
    onModalShown() {
        const modal = document.getElementById('ccAcivateModal');
        if (modal) {
            // Find the first button element in the modal
            const firstButton = modal.querySelector('button');
            if (firstButton) {
                firstButton.focus();
            }
        }
    }

    openOffCanvas() {
        window.openCookieConsentManager();
    }

    loadContent() {
        let _this = this,
            elements = document.querySelectorAll(this.options.cookieCheckContentSelector);

        // Try to set cookies first
        Iterator.iterate(elements, (el) => {
            let cookieId = el.dataset.acriscookieid;

            if(cookieId && !_this._loadContentCookies.includes(cookieId)) {
                _this._loadContentCookies.push(cookieId);

                CookieStorage.setItem(cookieId, '1', '30');
            }
        });

        // Load content if cookie is set
        Iterator.iterate(elements, (el) => {
            switch (el.tagName.toLowerCase()) {
                case 'script':
                    this.handleLoadContentByScript(el);
                    break;
                case 'iframe': case 'img':
                    this.handleLoadContentByDataAttr(el);
                    break;
                case 'link':
                    this.handleLoadContentByDataAttr(el, 'href');
                    break;
            }
        });
    }

    handleLoadContentByScript(el) {
        let cookieId = el.dataset.acriscookieid;

        if (!cookieId)
            return;

        let storedCookie = CookieStorage.getItem(cookieId);

        if (storedCookie === false) {
            let script = el.nextElementSibling;

            if(script !== null && script.tagName.toLowerCase() === "script")
                script.remove();
        } else {
            let script = document.createElement('script'),
                scriptHtml = el.innerHTML,
                scriptSrc = el.src,
                scriptAsync = el.async,
                scriptDefer = el.defer;

            script.type = "text/javascript";
            script.classList.add('acris-clone');

            if(scriptHtml)
                script.innerHTML = scriptHtml;

            if(scriptSrc)
                script.src = scriptSrc;

            if(scriptAsync)
                script.async = scriptAsync;

            if(scriptDefer)
                script.defer = scriptDefer;

            el.after(script);
            // in some cases depending on the script loaded it can occur that there is duplicate html content after loading - we need to remove the original html
            el.remove();
        }
    }

    handleLoadContentByDataAttr(el, attr = 'src') {
        let cookieId = el.dataset.acriscookieid;

        if (!cookieId && !attr)
            return;

        let storedCookie = CookieStorage.getItem(cookieId);

        if (storedCookie === false) {
            if(attr === 'src') {
                el.src = "";
            } else if(attr === 'href') {
                el.href = "";
            }

            if(el.tagName.toLowerCase() === "iframe")
                el.style.display = "none";

            this.showNextContent(el);
        } else {
            if(attr === 'src') {
                el.src = el.dataset.src;
            } else if(attr === 'href') {
                el.href = el.dataset.href;
            }

            if(el.tagName.toLowerCase() === "iframe")
                el.style.display = "block";

            this.hideNextContent(el);
        }
    }

    /* not finished
    handleLoadContentBySource(el) {
        let cookieId = el.dataset.acriscookieid,
            sourceEls = DomAccess.querySelectorAll(el, 'source', false);

        if (!cookieId && !sourceEls.length)
            return;

        let storedCookie = CookieStorage.getItem(cookieId);

        Iterator.iterate(sourceEls, (sourceEl) => {
            let src = sourceEl.dataset.src;

            console.log(storedCookie);
            console.log(src);

            if (storedCookie === false) {
                sourceEl.src = "";
                this.showNextContent(el);
            } else {
                sourceEl.src = src;
                this.hideNextContent(el);
            }
        });
    }*/

    showNextContent(el) {
        let next = el.nextElementSibling;

        if(next !== null && next.classList.contains(this.options.noCookieSetInfoClass)) {
            next.style.display = 'block';
        }
    }

    hideNextContent(el) {
        let next = el.nextElementSibling;

        if(next !== null && next.classList.contains(this.options.noCookieSetInfoClass)) {
            next.style.display = 'none';
        }
    }
}

window.openCookieConsentManager = function() {
    var cookieNote = DomAccess.querySelector(document, '.acris-cookie-consent.has--accepted', false);
    if(cookieNote) cookieNote.classList.remove("has--accepted");
    var modal = DomAccess.querySelector(document, '#ccAcivateModal', false);
    if(modal) document.getElementById("ccActivateModalLink").click();
};
