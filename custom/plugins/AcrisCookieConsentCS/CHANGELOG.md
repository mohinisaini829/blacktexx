# 9.2.2
- Improved plugin compatibility with Shopware 6.7.

# 9.2.1
- Improved accessibility by adding missing ARIA attributes.

# 9.2.0
- Adds keyboard navigation support to the cookie modal for better accessibility.

# 9.1.2
- Correction of possible warnings in log files.

# 9.1.1
- Fixes a potential issue where content was displayed twice when using "data-acriscookieid" in JavaScript integrations.

# 9.1.0
- Default values of cookies can now also be adjusted in the admin. This is useful when custom cookies are defined, which should then be handled in the storefront via JavaScript.
- Change of default values in the plugin settings for new installations.
- Change of the button text "Accept cookies" to "Accept selected cookies".

# 9.0.0
- Compatibility with Shopware 6.7.
- Support for the following languages: de-DE, en-GB, nl-NL, fr-FR, es-ES, fi-FI, nn-NO, sv-SE, cs-CZ, pt-PT, tr-TR, da-DK, it-IT, pl-PL, bs-BA

# 8.0.6
- Further accessibility optimizations.

# 8.0.5
- Improved compatibility with Stripe payments.

# 8.0.4
- Accessibility optimizations.

# 8.0.3
- Optimization of compatibility with the Shopware standard cookie behavior and other plugins.
- Removal of no longer needed CSRF tokens.

# 8.0.2
- Improved admin compatibility with Shopware 6.6.10.*

# 8.0.1
- Change to the specified compatibility with Shopware.

# 8.0.0
- Improves plugin compatibility with Shopware 6.6.10.* versions.

# 7.0.23
- Optimization of the cookie group handling in the administration.

# 7.0.22
- Changes the cookie for origin information ("acris_cookie_landing_page|acris_cookie_referrer") as a non-standard cookie. This can now be deactivated in the administration.

# 7.0.21
- Performance optimizations related to 404 pages.

# 7.0.20
- Fixes a potential issue where automatically found cookie groups were created without names and description texts.

# 7.0.19
- Correction of possible warnings in log files for newer Shopware versions.

# 7.0.18
- Bugfix: Wrong type conversion for the cookie value removed.

# 7.0.17
- Optimization of the Shopware standard analytics implementation in connection with the cookie consent.

# 7.0.16
- Fixes a possible issue where a JavaScript error occurred on product pages when a YouTube video was embedded and after JavaScript was rebuilt in a newer Shopware 6 environment.

# 7.0.15
- Bugfix fixed a bug when sales channel is added in admin

# 7.0.14
- Fixes a problem where default values of cookies in the functional cookie group were no longer set correctly.

# 7.0.13
- Optimised SEO indexing of cookie modal.

# 7.0.12
- Adds the _gcl_gs cookie to the list of Google Conversion Tracking cookies.

# 7.0.11
- Optimisation of the Cleanup Scheduled Task in conjunction with Shopware 6.6.

# 7.0.10
- Fixes a possible problem when updating to Shopware 6.6.

# 7.0.9
- Fixes a problem where the session cookie was set twice.

# 7.0.8
- Fixes a problem where the cookie notice was displayed again on a new visit to the website if the customer had not accepted all cookies.
- Optimisation in connection with the Http cache, where the cookie notice was displayed again on certain pages even though the cookies had already been accepted.

# 7.0.7
- Fixes a problem where default values were no longer set for standard cookies.

# 7.0.6
- Fixes an error when triggering the cookie button in the standard cookie hint below

# 7.0.5
- Optimization of the plugin configuration.
- Optimization storage of the cookie confirmation in the cache.

# 7.0.4
- Fixes a possible problem where the cookie notice does not appear on various pages even though the cookie notice has not yet been accepted.

# 7.0.3
- Optimization of the cookie script inclusion.

# 7.0.2
- Optimize compatibility with other consent managers.
- Adds a setting to obtain renewed cookie consent.

# 7.0.1
- Shopware 6.6. optimizations.

# 7.0.0
- Compatibility with Shopware 6.6.

# 6.3.6
- Fixes a problem with the initial cookie acceptance.

# 6.3.5
- Customisation of the previously known cookies in relation to the chat software Tawk.

# 6.3.4
- Adds the Pinterest cookie to the list of cookies already known in advance.

# 6.3.3
- Form optimizations

# 6.3.2
- Fixes a problem with the accordion

# 6.3.1
- Code optimizations

# 6.3.0
- Google cookie consent mode v2 has been added

# 6.2.3
- Fixes a possible problem where the cookie modal window reappears on the page after accepting cookies.

# 6.2.2
- The loading of Javascript in the cookie settings has been optimized.

# 6.2.1
- Inserts the data attribute "data-nosnippet" for the cookie hint so that it is not used by crawlers to create snippets.

# 6.2.0
- From now on, TWIG code can also be inserted in the script field of cookies.

# 6.1.9
- Optimizes adding the allowed attributes for the HTML Sanitizer

# 6.1.8
- Optimzed attribute whitelist for html sanitizer therefore the cookie snippets will not get broken after saving in admin

# 6.1.7
- Fixes a possible problem when removing set cookies.

# 6.1.6
- Fixes a problem where the modal window was no longer closed correctly when accepting cookies.

# 6.1.5
- Setting the SameSite attribute for the cookies acris_cookie_first_activated, acris_cookie_landing_page and acris_cookie_referrer.

# 6.1.4
- Compatibility with Shopware >= 6.5.1.0 improved

# 6.1.3
- Optimised cookie group list page in administration.

# 6.1.2
- Restricted deletion of cookies that are default.

# 6.1.1
- Improved cookie listing pages performance in administration.

# 6.1.0
- Added configuration to show "Accept cookies" button after settings are opened.

# 6.0.8
- Fixes a problem where scripts stored with cookies that contain a comment at the beginning were not loaded.
- Fixes a problem where scripts stored with cookies were not loaded correctly in translations.

# 6.0.7
- Fixes an issue where scripts could no longer be added with a script tag on a cookie since Shopware 6.5.

# 6.0.6
- Optimised cookie modal on page loading.

# 6.0.5
- Fixed cookie modal closing when we click outside it.

# 6.0.4
- The loading of Javascript in the cookie settings has been optimized.

# 6.0.3
- Fixes a problem where cookies were still not active after accepting them.

# 6.0.2
- Adds the Mollie Payment cookie to the list of cookies already known in advance.

# 6.0.1
- Changes the cookie ID of the Google Conversion Tracking.

# 6.0.0
- Compatibility with Shopware 6.5.

# 5.1.3
- Optimisation in connection with AdBlockers.

# 5.1.2
- Fixes a possible problem when loading 404 pages.

# 5.1.1
- Fixes a problem where the cookie notice was displayed after the browser was reopened.

# 5.1.0
- Added plugin setting for cookie title / description layout.

# 5.0.5
- Integration of Matomo Tag Manager

# 5.0.4
- Fixed problem with setting cookie default values and Http cache

# 5.0.3
- Change of the plugin name and the manufacturer links.

# 5.0.2
- Improves plugin compatibility

# 5.0.1
- Not found page cache optimization.

# 5.0.0
- The loading of Javascript in the cookie settings has been optimized.
- Adjusted cookie settings in the admin interface.

# 4.4.0
- Cookie settings extended to load Javascript.

# 4.3.4
- Adds additional cookie ids for the hotjar cookies.

# 4.3.3
- Fixes a problem where the cookie notice kept appearing on 404 pages with an assigned experience world.

# 4.3.2
- Optimised loading of cookie styles from plugin configuration.

# 4.3.1
- Iframe display optimized.

# 4.3.0
- External content can now be loaded via defined cookies and HTML code.

# 4.2.0
- Adds a scheduled task that automatically deletes unknown cookies from the system after a specified period of time.

# 4.1.0
- Allows to change the position of the cookie buttons in the plugin setting.

# 4.0.5
- Fixes a page load problem when the URL does not exist

# 4.0.4
- Improved compatibility with the ACRIS Import Export plugin.

# 4.0.3
- Fixes a problem where cookies were still not active after accepting them.

# 4.0.2
- HTTP cache optimisations.

# 4.0.1
- Improved compatibility with Shopware >= 6.4.11.0.

# 4.0.0
- Compatibility with Shopware >= 6.4.11.0.

# 3.4.1
- Optimizes plugin image.
- Improves compatibility with Shopware >= 6.4.10.0.
- Optimizes plugin color in administration.

# 3.4.0
- Automatically sets functional cookies in the browser that originate from Shopware itself or a Shopware plugin and have a default value.
- Problem fixes in connection with the use of Google reCAPTCHA.

# 3.3.5
- Improve the regular expression of the detection of the session cookie so that other cookies containing "session-" are not also detected and approved. Add the ledgerCurrency cookie to the AmazonPay cookie.

# 3.3.4
- Compatibility with Shopware 6.4.8.0.

# 3.3.3
- Cache optimisations in conjunction with other plugins

# 3.3.2
- Fixes a problem with plugin activation.

# 3.3.1
- Fixes problems when using the PayPal cookie in conjunction with the Safari browser.

# 3.3.0
- Changing the IDs of the cookie buttons and cookie settings in the HTML code so that they are no longer hidden by the browsers' ad blockers.

# 3.2.0
- Added button same width option

# 3.1.8
- Code optimisations. Removal of old code parts.

# 3.1.7
- Optimization of the cookie hint in the mobile view

# 3.1.6
- Optimization of the modal window in mobile view when there is a lot of text.

# 3.1.5
- Fixes a possible issue where cookie and cookie groups were inserted with the wrong translation.

# 3.1.4
- Optimization in conjunction with other plugins. Fires the javascript event of the changed cookies only after the changes have also been transmitted to the backend.

# 3.1.3
- Adds the Google Tag Manager debug cookie to the list of cookies already known in advance.

# 3.1.2
- Fixes a possible problem where download files generated in the admin by the Import / Export module could not be downloaded.

# 3.1.1
- Fixes possible problems in connection with Internet Explorer.

# 3.1.0
- Changes the cookie ID of Google Analytics for the detection of the new cookie structure.
- Attention: The instructions for the Google Tag Manager have also changed!

# 3.0.7
- Fixes an issue where a critical PHP error occurs after a while.

# 3.0.6
- From now on, the title and the text of the functional cookie group inserted in the default can be changed.

# 3.0.5
- Fixes problems when changing the currency that it was still displayed incorrectly on pages saved in the Http cache.

# 3.0.4
- Optimisation in connection with the Shopware Http Cache

# 3.0.3
- Fixes a possible problem in connection with various third-party plug-ins.

# 3.0.2
- Optimize module of cookie group in administration.

# 3.0.1
- Performance optimisations

# 3.0.0
- Compatibility with Shopware >= 6.4.0.0.

# 2.8.10
- Improved compatibility with other plugins when opening the cookie notice.

# 2.8.9
- Fixes problems where the cookie notice keeps appearing due to a JavaScript problem when accepting cookies.
- Prevents problems that can be caused by special characters of various kinds.

# 2.8.8
- Fixes an issue where cookies from other third-party plugins are not automatically detected.

# 2.8.7
- Fix problem with language missing data.

# 2.8.6
- Removes the acrisCookieConsent.footerCmsPageLinkPrefixFirst text module and inserts 3 text modules instead acrisCookieConsent.footerCmsPageLinkPrefixFirstFirst, acrisCookieConsent.footerCmsPageLinkPrefixFirstSecond and acrisCookieConsent.footerCmsPageLinkPrefixFirstThird for better customisability.
- Removes deprecated snippet services.

# 2.8.5
- The Google Analytics cookie inserted by Shopware in the standard will no longer be added from now on unless Google Analytics has been marked as active in the sales channel or no tracking ID has been specified.

# 2.8.4
- Fixes a possible problem with the specific modification of cookies or cookie groups that can delete functional cookies.

# 2.8.3
- Fixes a problem where the maximum character length of the cookie ID is limited to 255 characters.

# 2.8.2
- Fixes a problem when reopening the browser and displaying the cookie hint if it has already been accepted.
- The description of the cookie groups is now also displayed as HTML in the storefront.
- Optimisation of cache handling in interaction with other ACRIS plugins.

# 2.8.1
- Fixes a problem where functional cookies are assigned to a sales channel and this can cause problems.
- If the automatic cookie recognition is deactivated, no extra request is made to the server to recognise the cookie.
- The setting "Expand cookie settings on page view" now has the desired effect.
- Cookies from Shopware plugins are also recognised if automatic cookie recognition is disabled.

# 2.8.0
- Adds a new cookie "acris_cookie_first_activated". This will save which cookies have already been accepted by the user for the first time. The update thus enables the referrer and landing page to be sent correctly to Google Analytics. Attention: An update of the Tag Manager configuration is required, as soon as this has already been configured for landing page and referrer.

# 2.7.0
- Addition of the Google Analytics Conversion Tracking Cookie _gac. Important: If the Google Analytics cookie was configured in the tag manager for an additional transfer of referrer and landing page, this ID must now be updated in the tag manager.

# 2.6.1
- Solves problems saving HTML text in the admin area of Shopware 6.3.x

# 2.6.0
- Adds the option for additional CMS page links such as an legal notice link.
- Adds the option to show a heading in the cookie hint.
- Adds the option to not transfer the cookie status to the DataLayer.

# 2.5.1
- Compatibility with Shopware 6.3.x.

# 2.5.0
- Saves the referrer and the first visited page of the user in separate cookies and adds them to the DataLayer.

# 2.4.0
- Allows the "Accept Cookies" button to be specified at the end of the information text instead of having it listed as a separate button.
- Fix loading correct privacy link from plugin settings.

# 2.3.0
- Adds a new event acrisCookieStateChanged to the DataLayer for better processing in the Google Tag Manager.
- Adds the individual cookies with the prefixes acrisCookie or acrisCookieUniqueId to the DataLayer for better processing in the Google Tag Manager.
- Extends the list of previously known cookies.

# 2.2.1
- Adds additional Information to the DataLayer for further usage in Google Tag Manager.

# 2.2.0
- Inserts the accepted cookies into the DataLayer so that the Google Tag Manager can react to them.
- Allows an immediate page reload when accepting the cookies.

# 2.1.2
- Solves a problem loading the cookies on the order confirmation page.
- Prevents problems in the storefront when loading the cookie script and in connection with other plugins.
- Inserts a missing snippet in the admin area.

# 2.1.1
- Extends the list of previously known cookies.

# 2.1.0
- Fixes a problem where Google Analytics is tracked multiple times over the default Shopware integration.
- Solves a problem where the cookie that is set when accepting is not available via Javascript.
- Solves a problem with different tracking behavior when confirming via a different button.
- Optimization of the regex cookie check in the storefront. 
- Optimization of the view of the cookies in the administration.
- Adds an additional option to reopen the note according to the new Shopware behaviour.

# 2.0.7
- Solves a problem on set cookies for different sales channels.

# 2.0.6
- Fixes a problem when other plugins insert single cookies into the cookie hint.

# 2.0.5
- Fixes a possible problem when adding previously known cookies.
- Extends the list of previously known cookies.

# 2.0.4
- Compatibility with Shopware >= 6.2.0.
- Repositioning of the Admin Menu entry.

# 2.0.3
- Fixes problems with changing the background color of the cookie hint.
- Optimizes the inheritance from other themes.

# 2.0.2
- Fixes a problem with the recognition of cookies from other plugins with different language configurations.

# 2.0.1
- Fixes a possible problem when updating the plugin if the plugin is inactive or not installed.

# 2.0.0
- Correct functionality with activated Http-Cache.
- Fix a problem on adding the default value of cookies set by other Shopware plugins.
- Solves a problem on automatically adding new cookies.

# 1.3.2
- Fixes possible problems when updating or uninstalling the plugin in connection with low database versions.

# 1.3.1
- Solves a potential problem on SASS variables are not found by Shopware on compiling the storefront theme on plugin activation.

# 1.3.0
- Includes registered cookies from other plugins. Fixes a problem in connection with the maintenance page. Improves the cookie listing view in the administration. Extends the list of known cookies.

# 1.2.1
- Fixes an error on calling a group from a not assigned cookie on request.

# 1.2.0
- Adds the possibility to disable automatic cookie recognition.
- Fixes an error when loading the cookie plugin on the page.

# 1.1.2
- Fixes a problem when loading cookies without assigned cookie group. Extends the list of pre known cookies.

# 1.1.1
- Solves a JavaScript problem when using Internet Explorer.

# 1.1.0
- Inserts the possibility for the display of a modal window. Adds excluded cookies to the list of accepted cookies. Fixes problems in the administration where detected cookies are not created first for the default language.

# 1.0.1
- Fixes possible Java Script problems if no non-functional cookies are present.

# 1.0.0
- Release
