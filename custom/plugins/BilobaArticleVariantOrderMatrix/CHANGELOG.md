# 4.0.0
- Established compatibility for 6.7

# 3.5.0
- Improvements for layout 1st group as selection, 2nd group as matrix

# 3.4.5
- Added fallback for missing media

# 3.4.4
- Removed erroneously displayed information

# 3.4.3
- Added translations

# 3.4.2
- Fix for flag "display stock"

# 3.4.1
- When the parent article is inactive, the variant matrix is not displayed

# 3.4.0
- Enhancement: Added debounce for input in matrix

# 3.3.0
- Adjustments display when matrix is only activated for certain sales channels

# 3.2.0
- Added translations
- Adjustments product detail page

# 3.1.0
- Added translations

# 3.0.3
- Expected total price is now also calculated when values in the matrix are adjusted automatically

# 3.0.2
- Fixed error when table was disabled for certain sales channels

# 3.0.1
- Fixed image switch error in standard table layout

# 3.0.0
- Updated for Shopware 6.6

# 2.8.3
- Added fallback for missing media

# 2.8.2
- Added translations

# 2.8.1
- When the parent article is inactive, the variant matrix is not displayed

# 2.8.0
- Enhancement: Added debounce for input in matrix

# 2.7.1
- Added translations

# 2.7.0
- Added translations

# 2.6.4
- Fixed error when table was disabled for certain sales channels

# 2.6.3
- Fixed image switch error in standard table layout

# 2.6.2
- Fixed display error related to article number

# 2.6.1
- Fixed issue where modal dialog could not be reopened

# 2.6.0
- Added article number display in the variant matrix. When using the Shopware variant selection layout, the Shopware selector is always active even if the variant matrix has inactive options.

# 2.5.0
- Removed empty columns from the table

# 2.4.0
- Updated styling to support more themes

# 2.3.1
- Changed styling to Bootstrap classes

# 2.3.0
- Update for image gallery when variant images change, corrected wrong URL path for image change

# 2.2.4
- Bug Fix: AddToCart error in product detail

# 2.2.3
- Added missing configuration for Shopware 6.5

# 2.2.2
- Template correction to display correct information based on plugin configuration

# 2.2.1
- Corrected wrong method call

# 2.2.0
- Added: Option for variant images to be displayed in the matrix

# 2.1.1
- Added: Product stock and cart button are now also hidden when prices are hidden

# 2.1.0
- Added: Matrix can be hidden for certain customer groups, Added: Prices in the matrix can be hidden for certain customer groups

# 2.0.3
- Update for image switch with different template types

# 2.0.2
- Added image switch for variants when table headers or quantity field is clicked

# 2.0.1
- After csfr token was removed from addToCart, SW min version had to be changed to 6.5.

# 2.0.0
- Added support for Shopware 6.5.

# 1.10.2
- Template correction to display correct information based on plugin configuration

# 1.10.1
- Corrected wrong method call

# 1.10.0
- Added: Option for variant images to be displayed in the matrix

# 1.9.1
- Added: Product stock and cart button are now also hidden when prices are hidden

# 1.9.0
- Added: Matrix can be hidden for certain customer groups, Added: Prices in the matrix can be hidden for certain customer groups

# 1.8.1
- Update for image switch with different template types

# 1.8.0
- Added image switch for variants when table headers or quantity field is clicked

# 1.7.4
- Made further minor corrections

# 1.7.3
- Fixed issue with third group as matrix layout and other fixes

# 1.7.2
- Added toggle for additional display of Shopware variant selection above the matrix

# 1.7.1
- Fixed total price error

# 1.7.0
- Update for Quick Order in variant listing

# 1.6.2
- Fixed BilobaArticleVariantOrderAddToCart initialization and stack price summation function for total price

# 1.6.1
- Stackprices are now considered when totalPrice is calculated

# 1.6.0
- Update for Shopware 6.4.11.1

# 1.5.4
- Rebuilt JavaScript for Shopware version 6.4.11.1

# 1.5.3
- Added condition to account for inactive variants.

# 1.5.2
- Fix for table display when certain option combinations are not available

# 1.5.1
- Fixed total price representation with two decimal places

# 1.5.0
- Fixed input field and dropdown if variant has no stock

# 1.4.9
- Feature: Added configuration option to hide variants with stock less than 1.

# 1.4.8
- Feature: Added total price in variant matrix and a toggle for total price activation.

# 1.4.7
- Fixed the issue with group identification so the correct variants can be displayed.

# 1.4.6
- Blocks have been added to the templates at the appropriate places

# 1.4.5
- Bugfix: Corrected condition in subscriber so that tier prices are displayed correctly

# 1.4.4
- Bugfix: Now showing correctly calculated prices and fixed tier price issue

# 1.4.3
- Variant matrix is now also displayed when the custom products layout is selected

# 1.4.2
- Critical: Fixed error with products without variants and fixed error for products with a single variant

# 1.4.1
- Improvement for the grouping feature from release 1.4.0. This can now be overridden at the product level.

# 1.4.0
- Added new layout: Variant matrix is only displayed for the last variant group. Other variant groups are represented by the Shopware variant switch. The variant matrix can also be grouped.

# 1.3.0
- Made compatible with SW 6.4.0.0

# 1.2.4
- Update for Shopware 6.3.5.2

# 1.2.3
- Changed scss and added conditions for certain layouts

# 1.2.2
- Bugfix: Added support for shop subfolders

# 1.2.1
- Bugfix: Re-initialize Bootstrap modal window

# 1.2.0
- There is now a quick order button in the product listing. The product matrix can be opened in an overlay without leaving the product listing. Tier price table can now be hidden in the configuration and tier prices are displayed instead of the net/gross unit price if available.

# 1.1.4
- Net prices are now displayed when these are selected under customer groups.

# 1.1.3
- Fixed cache issue

# 1.1.2
- Fixed variant price and maximum purchase calculation

# 1.1.1
- Minor width adjustment regarding the position of the matrix below the price

# 1.1.0
- Minor changes in installation.md, adjusted URLs for addToCart and Offcanvas, changed URL for retrieving single items, added condition for sale

# 1.0.3
- Fixed code quality issues

# 1.0.2
- Extended CustomField condition

# 1.0.1
- Condition added to check for CustomField existence

# 1.0.0
- Initial version BilobaArticleVariantOrderMatrix