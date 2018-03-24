# ALP - Academic Labbook Plugin
ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

## What does ALP do?
Here are the features implemented so far...
 - Multiple author support
 - Change logs for posts and pages
   - Unhides revisions meta box in editor by default
 - Force users to be logged in to view
   - Feeds still accessible using HTTP authentication
 - LaTeX support
 - DOI and arXiv shortcodes
 - Disable certain post meta fields mostly suited to commercial sites, like
   excerpts, trackbacks, tags, etc.
 - Modifies pages to work more like a wiki:
   - Remove authors and dates
   - Display a table of contents in the sidebar (generated from header elements)
 - Special ALP theme
   - Displays multiple authors
   - Displays author widget, to view lists of each authors' posts
   - Displays revision history
     - Recent revisions widget
   - Shows reference widget under posts/pages showing cross-references to/from other posts/pages
 - (Optional) Change names and behaviours of user types:
   - Administrator -> [unchanged]
   - Editor -> Researcher
   - Author -> Intern
   - Contributor -> [removed]
   - Subscriber -> [unchanged]
   - Excluded -> [new]
 - Hides some WordPress branding and news

...and here are features planned for the future:
 - Labbook theme
   - Display author, edited posts and recent comments widgets by default
   - Top bar for linking to other network sites?
 - Email alerts for certain things
   - New posts
   - Comments (already built-in, or at least should be)
 - Disable public access
   - Stop images being loaded from outside (might require htaccess changes?)
 - Author list widget
 - Disable private posts (kinda useless with forced login)
 - Advanced search:
   - Search within PDFs etc.
   - Advanced search page with options to search by revision, etc.
 - Optionally display institute logo (also on login page)
 - Shortcodes for linking to Git/SVN commits and other archives
 - Offline download, e.g. [Simply Static](https://wordpress.org/plugins/simply-static/)
 - LSC-specific extensions:
   - DCC shortcodes
     - Add entries to reference widget

## Future ideas
Some feature ideas not considered critical, but nice to have:
 - Outreach pages: share some posts/pages publicly.

## Requirements
In general, you should use the latest version of WordPress 4.x as that's the
branch that will be supported. With auto-updates to WordPress Core, this is
easy.

The plugin has only been tested on PHP7. You must have the [DOM extension](http://www.php.net/manual/en/book.dom.php) installed. You also cannot use
PHP via CGI if you wish to make the site private but still have syndication
feeds available to the user.

Your users should use up-to-date browsers. The theme bundled with ALP (Alpine)
uses CSS Grid, which is only available in recent versions of the most popular
browsers. The [browsers that don't support CSS Grid](https://caniuse.com/#feat=css-grid)
represent only around 5% of global usage as of March 2018. This project is not
concerned about losing sales from users running out of date browsers!

### Gutenberg
WordPress 5.0 will introduce a new editor interface called [Gutenberg](https://wordpress.org/gutenberg/).
This does not show some of the custom interface additions that ALP makes to the
standard editor, but it is not anticipated that support for these additions will
be difficult to add, eventually. Depending on how Gutenberg is added to WordPress 5.0,
ALP may optionally "deactivate" Gutenberg under the hood until support is added.

The impending arrival of Gutenberg means ALP will not, for the time being,
include changes to the TinyMCE editor, such as those provided by the (great)
[TinyMCE Advanced](https://wordpress.org/plugins/tinymce-advanced/) plugin. (While
the changes made by TinyMCE Advanced should not affect Gutenberg, some Gutenberg-
specific "blocks" may take over functionality of a lot of what TinyMCE Advanced
provides, such as table building.)

## Design principles
 - **Clean code**: there's an awful lot of terribly written code floating around
   in the WordPress ecosystem. This plugin attempts to conform to coding
   standards, and to interfere minimally with the default WordPress behaviour
   where possible.
 - **Modular**: most/all features can be enabled or disabled, and work
   independently from each other.

## Credits
ALP is a mixture of new code and code forked from other open source, GPL
licenced plugins and themes. The code specific to this plugin was authored by
[Sean Leavey](https://attackllama.com/).

The following list of plugins have been partialy adapted into ALP. All have been
modified in some way (e.g. admin settings, class and setting namespaces, features
added or removed, etc.), some more so than others. Some upstream bug fixes and
reports have also been pushed back to these plugins and WordPress itself:
 - [Co-Authors Plus](https://wordpress.org/support/topic/two-bug-fixes-for-author-page/)
 - [wpDirAuth](https://wordpress.org/support/topic/overriding-cookie-expiry-for-directory-authenticated-users/)
 - [WordPress Core](https://core.trac.wordpress.org/ticket/43613)

### Co-Authors Plus
Authors: Mohammad Jangda, Daniel Bachhuber, Automattic, Shepherd Interactive, Mark Jaquith  
Link: [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/)

Most of this is adapted for ALP verbatim, but the guest author feature has been removed.

### WP-Post-Meta-Revisions and Revision Notes
Authors: Adam Silverstein, Helen Hou-Sandí  
Links: [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions) and [Revision Notes](https://wordpress.org/plugins/revision-notes/)

These plugins inspired parts of the design of ALP's revisions feature, but the code in ALP
is not particularly based on either one.

### MathJax-LaTeX
Authors: Phillip Lord, Simon Cockell, Paul Schreiber  
Link: [MathJax-LaTeX](https://wordpress.org/plugins/mathjax-latex/)

Most of the functional code was adapted for ALP, but the options were reduced to just SVG
rendered MathJax for simplicity.

### Authenticator
Authors: Inpsyde GmbH
Link: [Authenticator](https://wordpress.org/plugins/authenticator/)

The core authentication code, and the feed HTTP authenticator, have been adapted. The special
settings page, private feed keys, cookie lifetime and XML-RPC and REST settings have been
removed.

### Simple Life
Author: Nilambar Sharma  
Link: [Simple Life](https://wordpress.org/themes/simple-life/)

Bootstrap was removed, and replaced with [CSS Grid](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout).
The overall visual theme has been retained, but many small tweaks have been made, and
support for other plugins like WooCommerce removed.