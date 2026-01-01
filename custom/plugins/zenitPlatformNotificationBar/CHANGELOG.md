# 5.0.1
- Accessibility: Removed `touchstart` events in favour of `click` events to ensure compatibility with keyboard and screen reader users.

# 5.0.0
- Compatibility: Established compatibility with Shopware 6.7.0.0.

# 4.3.1
- Optimization: Added 'striptags' filter to `aria-label` value.

# 4.3.0
- Optimization: Improved accessibility.

# 4.2.0
- Optimization: Added HTML support for text fields.

# 4.1.1
- Bugfix: Fixed a Javascript error

# 4.1.0
- Feature: Added new configuration to exclude customer groups.

# 4.0.1
- Bugfix: z-index issue with sticky-headers.

# 4.0.0
- Compatibility: Established compatibility with Shopware 6.6.

# 3.2.3
- Bugfix: z-index issue with sticky-headers.

# 3.2.2
- Optimization: Hide scrollbar.

# 3.2.1
- Optimization: Changed overflow scroll because of scrollbar.

# 3.2.0
- Optimization: Optimized display on mobile.
- Optimization: Refactored systemConfigService usage.

# 3.1.1
- Optimization: Rebuild javascript with new shopware version.

# 3.1.0
- Optimization: Display banner text as link, if display button option is disabled and link url is set.

# 3.0.0
- Compatibility: Established compatibility with Shopware 6.5.0.0.
- Compatibility: Replaced `data-toggle` with `data-bs-toggle`.
- Compatibility: Replaced `data-target` with `data-bs-target`.
- Compatibility: Revision of statemanager classes for bootstrap v5
- Compatibility: Migrated jQuery implementations to Vanilla JavaScript
- Compatibility: Changed path from `ThemeCompilerEnrichScssVariablesEvent`.
- Optimization: Removes `data-btnCloseBanner` attribute, because it is already in plugin options.
- Optimization: Removed `role="button"` attribute on buttons, because it is not necessary.
- Optimization: Added deployment server check in ThemeVariablesSubscriber.

# 2.5.0
- Optimization: Add default media folder.
- Optimization: Improves the initial loading of the text slider.
- Optimization: Register cookie to comfort cookie group due to legal restrictions.
- Bugfix: Prevents subsequent slides from overlaying during initial rotation.

# 2.4.0 
- Optimization: Set lower z-index because of sticky headers.
- Optimization: Added translation of custom configuration alerts.
- Optimization: Removed deprecated SessionInterface.
- Optimization: Autoloading of Storefront snippets.
- Optimization: Removed SCSS Workaround from Issue NEXT-7365.
- Compatibility: Shopware 6.4.18

# 2.3.1
- Feature: Added new configuration to change cookie expiration time.

# 2.3.0
- Optimization: Changed open and closed status from session storage to cookies.
- Optimization: Status is now cookie controlled and therefore available in other tabs.
- Optimization: Prevents bumping banner height by text-slider initialization.
- Optimization: Improves text slider reinitialization on collapse show event.
- Optimization: Text Slider starts by first text slide on collapse show event.
- Optimization: Added custom component to display config alerts.
- Bugfix: Display in search controller did not work.

# 2.2.1
- Optimization: Register js-plugins to other selectors for compatibility with Shopware 6.4.8.0

# 2.2.0
- Optimization: Improves the help texts in the plugin configuration.
- Optimization: Improvement in SCSS Code.
- Feature: Added new configuration to choose between default fonts and custom fonts.
- Feature: Added the ability to translate texts.

# 2.1.3
- Optimization: Centers the text slider if no button is displayed.

# 2.1.2
- Optimization: Removed pagetype-selection for shoppages, since Shopware no longer differentiates between shop pages and category pages within the controller.
- Optimization: Improves the compatibility with custom controllers in the GetControllerInfo class.
- Optimization: Removes the removeConfiguration function from the Bootstrap plugin

# 2.1.1
- Optimization: Revision of the "Display on" option
- Optimization: Revision of the plugin configuration
- Optimization: Renamed Twig-Variables from `notificationBar` into `zenNotificationBar`
- Optimization: Renamed SCSS-Variables from `notification-bar` into `zen-notification-bar`
- Optimization: Quoting the values of url() to prevent display problems on URLs with special characters.
- Optimization: Revision of the bootstrap plugin lifecycle methods

# 2.1.0
- Feature: New mode: "collapsable". Banner is closed permanently via a close button. Initially the banner appears expanded.
- Feature: New mode: "expandable". The banner is opened using an arrow button. Initially, the banner appears closed.
- Optimization: Mobile Friendly Lighthouse Check - Optimization of the clickable areas
- Optimization: improvement of the button spacing

# 2.0.1
- Optimization: Lighthouse Accessibility improvement

# 2.0.0
- Compatibility with Shopware 6.2.0
- Feature: Introducing new media selection in plugin configuration, which is available since SW 6.2.0
- Feature: Introducing new color picker selection in plugin configuration, which is available since SW 6.2.0
- Feature: New Subscriber to add custom SCSS variables through plugin config - NEXT-5116
- Optimization: New Twig block chosen as entry point
- Bugfix: Prevents escaping of custom-css configuration output.

# 1.2.1
- Bugfix: Visible viewports configuration.
- Bugfix: Smaller bugfixes in the display.

# 1.2.0
- Optimization: Improved detection of the collapsed condition. Storage until change in the plugin configuration.
- Optimization: Adjust the vertical alignment of content for Chrome.

# 1.1.0
- Compatibility with Shopware 6.1.0

# 1.0.1
- Optimization of the text slider line height

# 1.0.0
- Initial plugin release
