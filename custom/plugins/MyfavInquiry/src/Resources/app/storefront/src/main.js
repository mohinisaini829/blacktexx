import MyfavInquiryAddPlugin from './plugin/myfav-inquiry-add.plugin'
import MyfavInquiryCountPlugin from './plugin/myfav-inquiry-count.plugin'
import MyfavSpecialOffersCheckboxPlugin from './plugin/myfav-special-offers-checkbox.plugin'

// register plugins
const PluginManager = window.PluginManager;
PluginManager.register('MyfavInquiryAddPlugin', MyfavInquiryAddPlugin, '[data-myfav-inquiry-add-plugin-options]');
PluginManager.register('MyfavInquiryCountPlugin', MyfavInquiryCountPlugin, '[data-myfav-inquiry-count]');
PluginManager.register('MyfavSpecialOffersCheckboxPlugin', MyfavSpecialOffersCheckboxPlugin, '.special-offers-checkbox');

// bootstrap custom file input js see: https://www.w3schools.com/bootstrap4/bootstrap_forms_custom.asp
$(".custom-file-input").on("change", function() {
    var fileName = $(this).val().split("\\").pop();
    $(this).siblings(".custom-file-label").addClass("selected").html(fileName);
});
