import MyfavInquiryAddPlugin from './plugin/myfav-inquiry-add.plugin'
import MyfavInquiryCountPlugin from './plugin/myfav-inquiry-count.plugin'
import MyfavSpecialOffersCheckboxPlugin from './plugin/myfav-special-offers-checkbox.plugin'

// register plugins
const PluginManager = window.PluginManager;
PluginManager.register('MyfavInquiryAddPlugin', MyfavInquiryAddPlugin, '[data-myfav-inquiry-add-plugin-options]');
PluginManager.register('MyfavInquiryCountPlugin', MyfavInquiryCountPlugin, '[data-myfav-inquiry-count]');
PluginManager.register('MyfavSpecialOffersCheckboxPlugin', MyfavSpecialOffersCheckboxPlugin, '.special-offers-checkbox');

// bootstrap custom file input js see: https://www.w3schools.com/bootstrap4/bootstrap_forms_custom.asp
document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.custom-file-input').forEach(function(input) {
        input.addEventListener('change', function() {
            var fileName = this.value.split("\\").pop();
            var label = this.parentElement.querySelector('.custom-file-label');
            if (label) {
                label.classList.add('selected');
                label.textContent = fileName;
            }
        });
    });
});
