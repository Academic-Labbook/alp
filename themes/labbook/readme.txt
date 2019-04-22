=== Labbook ===
Contributors: seanleavey
Tags: custom-background, custom-logo, custom-menu, editor-style, education, theme-options, threaded-comments, translation-ready, two-columns
Requires at least: 5.0.0
Tested up to: 5.0.3
Stable tag: 1.1.8
Requires PHP: 7.0.0
License: GNU General Public License v3 or later
License URI: LICENCE

A clean, simple theme which, when used in combination with [Academic Labbook Plugin](https://alp.attackllama.com/),
adds many useful features to your site for logging laboratory work in multi-user scientific or
academic contexts such as universities and organisations.

This theme is intended to be used in combination with the electronic laboratory notebook plugin
[Academic Labbook Plugin](https://alp.attackllama.com/), which provides a back-end for many of the
features this theme displays. While it is possible to use this theme without the aforementioned
plugin, only with it installed and active will you benefit fully from this theme's features, such as
the display of post coauthors, unread posts for logged-in users, and edit histories and summaries
for posts and pages.

This theme is not intended for mainstream, commercial sites; as such, modern browsers that support
the CSS features [Grid](https://caniuse.com/#feat=css-grid) and [Flexbox](https://caniuse.com/#feat=flexbox)
are required. Older browsers, or those which do not yet implement these features, may encounter
layout issues.

== Description ==

This is a basic theme supporting features useful for logging laboratory work in an academic context.
If used in combination with Academic Labbook Plugin, extra features such as display of coauthors,
revisions and cross-references are enabled.

Note that Academic Labbook Plugin is still in beta and not yet available on WordPress.org. If you
wish to obtain it, you must download and install it manually (see [instructions](https://alp.attackllama.com/documentation/installation/)).

== Installation ==

Note: this plugin requires the PHP DOM extension in order to properly render page tables of
contents. If this extension is not available, tables of contents will be unavailable.

1. (Optional but highly recommended): install [Academic Labbook Plugin](https://alp.attackllama.com/).
2. In your admin panel, go to Appearance > Themes and click the Add New button.
3. Click Upload Theme and Choose File, then select the theme's .zip file. Click Install Now.
4. Click Activate to use your new theme right away.

== Frequently Asked Questions ==

= Does this theme support any plugins? =

Labbook supports Academic Labbook Plugin - indeed, use of it is highly encouraged to make the most
of features provided in this theme.

== Changelog ==

= 1.1.8 - 2019-04-22 =
* Hotfix: removed incorrectly displayed post ID.

= 1.1.7 - 2019-04-22 =
* Set relative body font size instead of absolute, so users with non-default font size settings see
  correspondingly scaled fonts.
* Added post navigation links under single posts, and restyled post page navigation links.
* Hid table of contents customizer setting when the server doesn't support it.
* Fixed bug with Academic Labbook Plugin detection on networks.

= 1.1.6 - 2019-04-17 =
* Minor internal fixes.

= 1.1.5 - 2019-04-16 =
* Fixed bug with sidebar positioning on smaller screens.
* Fixed notice when ALP plugin is disabled.
* Removed misleading comment numbering.
* Move code from Labbook theme into ALP plugin (now requires 0.15.0).

= 1.1.4 - 2019-04-07 =
* Prevented unread flag buttons showing up on pages in search results.
* Reduced font size of drop-down boxes in sidebar to match text.

= 1.1.3 - 2019-04-01 =
* Fixed bug with revision pagination links.

= 1.1.2 - 2019-03-19 =
* Fixed bug with read flags on single pages.

= 1.1.1 - 2019-03-10 =
* Added support for advanced searches.
* Fixed theme activation bug on networks.

= 1.1.0 - 2019-03-06 =
* Added support for setting read flags (Academic Labbook Plugin feature) via AJAX.
* Stopped theme customizer settings from showing if ALP is not enabled or if the corresponding
  option providing the functionality relied upon by the theme is not enabled.
* Fixed bug with timezones when displaying revision dates.
* Enforced minimum PHP version (7.0.0) on activation.
* Requires WordPress 5.1 or higher.

= 1.0.7 - 2019-01-29 =
* Made visited post titles show a different colour to non-visited ones.
* Fixed bug with contents generator, when the page content contained non-ASCII characters.
* Added padding to text on content-none and 404 pages.
* Made background on revisions tables for autosaves/current/original posts lighter.
* Allow flexible header image widths.
* Minor CSS fixes.

= 1.0.6 - 2019-01-19 =
* Added markup support in post titles.
* Stopped long post titles overflowing the entry.
* Removed post anchor appearing when hovering over title in favour of using the ID field as a
  permalink.
* Fix separator (`hr`) styling when using block editor separator.
* Minor CSS fixes.

= 1.0.5 - 2019-01-16 =
* Changed edit summary list under posts and pages to a table.
* Now page links in edit summary table scroll back to the table at their destination.
* Footer only shown if there is contents.

= 1.0.4 - 2019-01-13 =
* Post edits now avoid showing drafts made before publication.
* Fixed bug with revision list pagination.
* Minor CSS fixes.

= 1.0.3 - 2019-01-12 =
* Editor style now closely matches the front end.
* Bug fix for edit counts, where number of edits was shown including the revision created upon
  publication, leading to an error of 1 in some cases.
* Edit count not shown in post/page headers where there have been no edits so far.
* Additional filtering on vulnerable output strings.
* Minor layout fixes.

= 1.0.2 - 2019-01-08 =
* Initial release

== Credits ==

* [Sean Leavey](https://attackllama.com/)
* Based on Underscores https://underscores.me/, (C) 2012-2017 Automattic, Inc., [GPLv2 or later](https://www.gnu.org/licenses/gpl-2.0.html)
* [normalize.css](https://necolas.github.io/normalize.css/), (C) 2012-2016 Nicolas Gallagher and Jonathan Neal, [MIT](https://opensource.org/licenses/MIT)
* Look and feel based on [Simple Life](https://wordpress.org/themes/simple-life/), by Nilambar Sharma, [GPLv3 or later](http://www.gnu.org/licenses/gpl-3.0.html)
* Fonts from [FontAwesome](https://fontawesome.com/)
