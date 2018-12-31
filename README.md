# ALP - Academic Labbook Plugin
[![Build Status](https://travis-ci.com/SeanDS/alp.svg?branch=master)](https://travis-ci.com/SeanDS/alp)

ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

## What does ALP do?
Here are the features implemented so far...
 - Allows multiple authors to be assigned to single posts
 - Provides change logs for posts and pages with user-defined comments
 - Shows list of cross-references made between posts and pages
 - Option to force users to be logged in to view
   - Feeds still accessible using HTTP authentication
   - Images still accessible with direct link (not possible to block without server configuration)
 - Disable trackbacks, which are usually only useful for commercial sites.
 - Modifies pages to work more like a wiki:
   - Removes authors and dates
   - Displays a table of contents in the sidebar (generated from header elements)
   - Shows breadcrumb trail back to home page
 - Special ALP theme
   - Displays multiple authors
   - Displays author widget, to view lists of each authors' posts, including those coauthored
   - Displays revision history under posts and pages
     - Recent revisions widget for sidebar
   - Shows reference widget under posts/pages showing cross-references to/from other posts/pages
   - Optionally display institute logo and icon
   - Optionally shows contents on page sidebars
 - Optionally changes user roles:
   - *Administrator* is unchanged
   - *Editor* is renamed *Researcher*
   - *Author* is renamed *Intern*
   - *Contributor* is removed
   - *Subscriber* is unchanged
   - *Excluded* is added (for keeping ex-users' posts, comments, etc. on record but not giving them access)
 - Hides some WordPress branding and news
 - Supports custom media (MIME) upload types

## Future ideas
Some ideas pondered for the future:
 - Labbook theme
   - Display author, edited posts and recent comments widgets by default
   - Top bar for linking to other network sites?
 - Configurable email alerts for certain things
   - New posts
   - Comments on other posts
 - Disable private posts (kinda useless with forced login)
 - Advanced search:
   - Search within PDFs etc.
   - Advanced search page with options to search by revision, etc.
 - Gutenberg block for linking to Git/SVN commits and other archives
 - Gutenberg block for DOI and arXiv references

## Requirements

### WordPress
You should use the latest version of WordPress, but at very least 4.9.6 as it
provides features used by ALP. WordPress 5.0 will introduce the Gutenberg
editor which ALP fully supports.

It is desirable, but not required, to use WordPress in [multisite](https://codex.wordpress.org/Create_A_Network)
mode. This exposes additional options to network administrators to control upload
media (MIME) types and custom script paths.

### PHP
The plugin has only been tested on PHP7. You must have the [DOM extension](http://www.php.net/manual/en/book.dom.php) installed in order for the page
table of contents lists to work. You also cannot use PHP via CGI if you wish
to make the site private but still have syndication feeds available to the user.

### Clients
Your users should use up-to-date browsers. The theme bundled with ALP (Alpine)
uses CSS Grid, which is only available in recent versions (i.e. within the last
two years) of the most popular browsers. The [browsers that don't support CSS Grid](https://caniuse.com/#feat=css-grid)
represent only around 5% of global usage as of March 2018. This project is not
concerned about losing sales from users running out of date browsers!

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
added or removed, etc.), some more so than others. Some upstream bug reports and
fixes have also been pushed back to these plugins and WordPress itself:
[1](https://wordpress.org/support/topic/two-bug-fixes-for-author-page/),
[2](https://wordpress.org/support/topic/overriding-cookie-expiry-for-directory-authenticated-users/),
[3](https://core.trac.wordpress.org/ticket/43613),
[4](https://core.trac.wordpress.org/ticket/43629),
[5](https://core.trac.wordpress.org/ticket/43705),
[6](https://github.com/Automattic/Co-Authors-Plus/pull/441#issuecomment-386415103),
[7](https://github.com/Automattic/Co-Authors-Plus/pull/457#issuecomment-386429553),
[8](https://github.com/Automattic/Co-Authors-Plus/issues/513),
[9](https://github.com/Automattic/Co-Authors-Plus/issues/514),
[10](https://github.com/Automattic/Co-Authors-Plus/issues/562)
[11](https://github.com/WordPress/gutenberg/issues/6688),
[12](https://github.com/WordPress/gutenberg/issues/6703),
[13](https://github.com/WordPress/gutenberg/issues/6704),
[14](https://github.com/WordPress/gutenberg/issues/8032)
[15](https://github.com/WordPress/wordpress-importer/issues/40)
[16](https://github.com/WordPress/gutenberg/issues/10834)

### Co-Authors Plus
Authors: Mohammad Jangda, Daniel Bachhuber, Automattic, Shepherd Interactive, Mark Jaquith  
Link: [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/)

This plugin was originally adapted with light changes but has since been heavily modified.

### WP-Post-Meta-Revisions and Revision Notes
Authors: Adam Silverstein, Helen Hou-Sandí  
Links: [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions) and [Revision Notes](https://wordpress.org/plugins/revision-notes/)

These plugins inspired parts of the design of ALP's revisions feature, but the code in ALP
is not particularly based on either one.

### WP-KaTeX
Author: Andrew Sun  
Link: [WP-KaTeX](https://wordpress.org/plugins/wp-katex/)

The JavaScript function used to enable KaTeX rendering for particular elements was adapted
in ALP.

### Authenticator
Authors: Inpsyde GmbH  
Link: [Authenticator](https://wordpress.org/plugins/authenticator/)

The core authentication code, and the feed HTTP authenticator, have been adapted. The special
settings page, private feed keys, cookie lifetime and XML-RPC and REST settings have been
removed.

### Simple Life
Author: Nilambar Sharma  
Link: [Simple Life](https://wordpress.org/themes/simple-life/)

The core theme has been forked, but with many features added, removed or changed. Bootstrap was
removed and replaced with [CSS Grid](https://developer.mozilla.org/en-US/docs/Web/CSS/CSS_Grid_Layout).