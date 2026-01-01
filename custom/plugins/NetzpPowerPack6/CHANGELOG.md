# 3.0.3
- Fix: The additional settings of a CMS block (visibility, colors etc.) were not always displayed correctly.

# 3.0.2
- Fix: Fixed an error related to time zones that occurred in rare cases.

# 3.0.1
- Fix: Google Maps element: Special characters such as quotation marks/HTML entities in the content are displayed correctly in the map popup

# 3.0.0
- Support for SW 6.6

# 2.2.0
Change: the elements "collapse" and "tabs" now have an optional title
- Fix: non-existent or deleted CMS pages no longer trigger an error (header, footer, order completion)

# 2.1.2
- Fix: accordion element modified for bootstrap 5

# 2.1.1
- Fix: Asset paths corrected for SW >= 6.5.4

# 2.1.0
- Change: Images / responsive thumbnail sizes optimized

# 2.0.0
- Support for SW 6.5
- !!!Important!!! The responsive display selector has now been integrated directly into the Shopware 6 standard. Therefore, the responsive display options in PowerPack will be dropped in the next plugin version. Please migrate your settings as soon as possible and use only the Shopware standard under Visibility / Devices.
* +++ ATTENTION +++ **Update to SW 6.5**
* First deactivate all plugins (do not uninstall them!).
* Then update the store to SW 6.5
* Then update the plugins to the compatible version for SW 6.5
* Activate all plugins again
* Perform the update for each single plugin (click on the version number of each plugin)
* Shopware has made significant changes in version 6.5. The adaptation of our plugins here was very complex and took a lot of time.
* If something does not work as expected, please contact our plugin support at https://plugins.netzperfekt.de/support.

# 1.6.0
- Component "Google maps": Opt-In / the maps are optionally loaded only after consent.

# 1.5.4
- "Google maps" component: callback added to prevent google api error

# 1.5.3
- "Infobar" component: CSS styles corrected

# 1.5.2
- For images the smaller resolutions of the Shopware media standard are used.

# 1.5.1
- The parallax element is unfortunately not supported on mobile devices for technical reasons, but the images are now scaled correctly there.

# 1.5.0
- Change: the counter element can be used with placeholders ({counter} {start} {end})
- Fix: The slots are shown in the correct order in categories/tab layout

# 1.4.0
- Change: CMS blocks and sections: Visibility can be controlled via a Rule Builder rule
- Change: CMS blocks and sections: Visibility can be controlled by date (show from - to)

# 1.3.0
- Change: action card / new type "static": fully clickable element
- Change: Grid element 2-columns 4/8: responsive layout for tablets has been changed to col-md-6/6 (instead of col-md-4/8)
- Fix: Admin / HTML/CSS element: minimum height to allow editing of settings for empty elements.
- Fix: font awesome / removed facebook icon (interference with our plugin NetzpShariff6)

# 1.2.1
- Fix: admin / small display problems fixed
- Fix: support for installation via composer
- Fix: action card / open link in new window now working correctly
- Change: action card / blur: text can be excluded from blur effect

# 1.2.0
**This is a really big update with lots of new features and elements as well as some minor bug fixes.
In particular, there is a new _CTA Flex_ element that simplifies the design of buttons and call-to-actions that is based on CSS/Flexbox. The _old CTA element_ has been retained as the two elements are not compatible. However, from now on only the new _CTA Flex_ should be used for CTA / Buttons!**

- Change: new element "CTA Flex" - fundamentally revised button and call-to-action element based on CSS Flexbox
- Change: new element "Action card with flip/reveal/blur/popup
- Change: new element "Before/after image comparison/slider
- Change: new element "Animated counter"
- Change: analog to the standard Shopware element "3 columns, image & text" there are two elements with 2 and 4 columns, image & text (under "PowerPack - Layouts")
- Change: Shopware data mapping is supported for images and text where it makes sense (Alert, Card, CTA / CTA2, ImageCompare, Parallax, Testimonial)
- Change: Color gradients can be defined for all elements (also for Showpare standard elements) (in block settings and also section settings)
- Change: Admin / block categories split into "PowerPack Layouts" and "PowerPack Elements" for better overview
- Fix: Admin / Tab element: editing many tabs has been improved, maximum number of tabs has been increased to 20
- Fix: Admin / Collapse element: the maximum number of collapsible entries has been increased to 20
- Fix: admin / Testimonial element: display was optimized if no name was entered
- Fix: display for category filters inserted in grid layouts has been optimized, the filter popup is no longer cut

# 1.1.2
- Change: plugin settings / Font Awesome can be optionally excluded in the frontend

# 1.1.1
- Version: compatibility with SW 6.4.7

# 1.1.0
- ESLint problems fixed when building assets / compiling

# 1.0.9
- Version: compatibility with SW 6.4

# 1.0.8
- Fix: admin / CTA element: media handling fixed for images
- Change: toggle responsive state for cms sections and blocks

# 1.0.7
- Fix: snippets also replaced for ProductEntity (not only SalesChannelProductEntity)

# 1.0.6
- Fix: CTA / Optimized button display on mobile devices
- Change: Collapse-Element: minimum entries set to 1

# 1.0.5
- Fix: on some systems there was an error message during installation (ThemeCompiler) in connection with FontAwesome. This has been - now really ;-) fixed

# 1.0.4
- Fix: on some systems there was an error message during installation (ThemeCompiler) in connection with FontAwesome. This has been fixed
 
# 1.0.3
- snippets and other twig expressions can be used in article names, article descriptions and category descriptions, just type {{ "snippetname" | trans}} or {{ 40+2 }} or something
  (if you want html in your snippets use {{ "snippetname" | trans | raw }})

# 1.0.2
- sales channel: header cms block can be "sticky"
- new cms element: infobar/icon bar with 2 layouts
- new cms element: parallax image
- element counter: new layout "boxes"
- new cms grids 2 columns (4/8 and 8/4)

# 1.0.1
- optional cms layout on order finish page
- new cms element: google map
- new cms element: countdown

# 1.0.0
- initial version
