export default class AcrisCookieConfigurationOverride extends window.PluginBaseClass {
    init() { }

    openOffCanvas() {
        window.openCookieConsentManager();
    }
}
