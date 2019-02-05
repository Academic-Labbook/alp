# ALP - Academic Labbook Plugin
[![Build Status](https://travis-ci.com/SeanDS/alp.svg?branch=master)](https://travis-ci.com/SeanDS/alp)

ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

Lots of documentation is provided on the [ALP website](https://alp.attackllama.com/).
Before installing, please read and understand the `Development plans in the context of WordPress upgrades`
section below. It explains how new features in WordPress will be tracked by ALP.

## What does ALP do?
 - Allows multiple authors to be assigned to single posts
 - Provides change logs for posts and pages with user-defined comments
 - Shows list of cross-references made between posts and pages
 - Provides TeX block for rendering mathematical markup
 - Option to force users to be logged in to view
   - Feeds still accessible using HTTP authentication
   - Images still accessible with direct link (not possible to block without server configuration)
   - XML-RPC interface disabled
 - Disable trackbacks, which are usually only useful for commercial sites
 - Modifies pages to work more like a wiki:
   - Removes authors and dates
   - Displays a table of contents based on page headers (when used with Labbook theme)
   - Shows breadcrumb trail back to home page (when used with Labbook theme)
 - Supports custom media (MIME) upload types (when used on a network)
 - Optionally changes user roles:
   - *Administrator* is unchanged
   - *Editor* is renamed *Researcher*
   - *Author* is renamed *Intern*
   - *Contributor* is removed
   - *Subscriber* is unchanged
   - *Excluded* is added (for keeping ex-users' posts, comments, etc. on record but not giving them
     access)
 - Overrides some default settings in the editor, such as to make images in posts link to their
   full-sized versions.
 - Hides some WordPress branding and news

## Labbook theme
ALP is intended to be used with the specially created "Labbook" theme, which supports display of
multiple authors, edit summaries, cross-references, etc. provided by ALP.

Features:
 - Displays multiple authors
 - Displays revision history under posts and pages
 - Displays breadcrumb trail on pages showing page hierarchy
 - Displays table of contents on pages based on page headings
 - Provides users sidebar widget showing links to view lists of each authors' posts, including those
   coauthored
 - Provides recent revisions sidebar widget showing recently edited posts and pages
 - Shows cross-references (links in the post body) between posts and pages under each post or page
 - Supports display of an institute logo and icon
 - Provides two menu locations for providing links to site or external pages or URLs
 - Provides customisable copyright notice and ability to hide branding
 - Responsive to screen size: viewable on mobile, tablet and desktop browsers

## Requirements

### WordPress
WordPress 5.0.0 or newer is required. ALP adds a block to and extends the sidebar of the new editor
provided in WordPress 5.

It is desirable, but not required, to use WordPress in [multisite](https://codex.wordpress.org/Create_A_Network)
mode. This exposes additional options to network administrators to control upload media (MIME) types
and custom script paths.

### PHP
The plugin has only been tested on and only supports PHP 7. You must have the [DOM extension](http://www.php.net/manual/en/book.dom.php)
installed in order for the page table of contents lists provided by the Labbook theme to work. You
also cannot use PHP via CGI if you wish to make the site private but still have syndication feeds
available to the user.

### Clients
Your users should use up-to-date browsers. The Labbook theme uses CSS Grid, which is a web standard
only supported in relatively recent browsers (i.e. within the last three years) of the most popular
browsers. The [browsers that don't support CSS Grid](https://caniuse.com/#feat=css-grid) represent
only around 5% of global usage as of March 2018. There are very little legitimate reasons to use
outdated browsers, and this project is not concerned about losing sales from users running them!

## Design principles
 - **Clean code**: there's an awful lot of terribly written code in the WordPress ecosystem. This
   plugin attempts to conform to coding standards, and to interfere minimally with the default
   WordPress behaviour where possible. For some of the more major features, like coauthors,
   some quiet major modifications are required to core behaviour, which means that ALP may not be
   compatible with certain other plugins.
 - **Modular**: most/all features can be enabled or disabled, and work independently from each
   other.

## Future development plans
The basic behaviour of ALP is already in place, but the plan is to keep adding useful features
as ALP is used by researchers and the developer gets feedback. Please take a look at the project's
[issue tracker](https://github.com/SeanDS/alp/issues/) to view, comment on and add your own feature
requests for future releases.

### Development plans in the context of WordPress upgrades
The intention is for ALP to track the latest improvements to WordPress and Gutenberg as much as
possible. *Any feature that is provided by ALP that becomes available in WordPress Core will
probably be removed from ALP quite soon after*. The author does not have enough time to support
many different versions of WordPress, and so the latest release will always be the focus. If a
new core WordPress feature is released in the future that does something very similar to what ALP
provides, then it is likely that the corresponding ALP feature will be removed. If in order to
avoid breaking installations, some maintenance is required, instructions will be provided for
system administrators to perform the necessary changes and the ALP release removing the feature will
be given a new major version number.

WordPress is in a period of transition: the new [Gutenberg](https://wordpress.org/gutenberg/) editor
has introduced a completely different way of writing content. The feature roadmap shows a plan to
eventually replace most of the configurable parts of WordPress with Gutenberg-based assets.

Two changes of particular note are:

  - The navigation menu and sidebar will probably eventually be
    [converted](https://make.wordpress.org/core/2018/12/08/9-priorities-for-2019/) to use Gutenberg
    blocks, with existing widgets and menu configurations becoming "legacy" types. The widgets
    provided by ALP will likely therefore be made into blocks.
  - There is a possibility that a table of contents block will become part of the core WordPress
    block library (see [this](https://github.com/WordPress/gutenberg/issues/11047),
    [this](https://github.com/WordPress/gutenberg/issues/7115) and
    [this](https://github.com/WordPress/gutenberg/issues/6182)). The ALP table of contents block is
    auto-generated on page load, so it probably won't disrupt too much to use the new block instead.

## Credits
This plugin was entirely authored by [Sean Leavey](https://attackllama.com/), but in some cases
code was adapted from other GPL licenced plugins. Features from the list of plugins below have
inspired features in ALP. Some bug reports and fixes discovered during the making of ALP have also
been pushed back to these plugins and WordPress itself:
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
[17](https://github.com/adamsilverstein/mathml-block/issues/12)

### Co-Authors Plus
Authors: Mohammad Jangda, Daniel Bachhuber, Automattic, Shepherd Interactive, Mark Jaquith
Link: [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/)

Some of the code from this plugin has been adapted into ALP, but the main behaviour has been
heavily modified to function using the block editor in WordPress 5.

### WP-Post-Meta-Revisions and Revision Notes
Authors: Adam Silverstein, Helen Hou-Sandí
Links: [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions) and [Revision Notes](https://wordpress.org/plugins/revision-notes/)

These plugins inspired parts of the design of ALP's edit summaries feature.

### Authenticator
Authors: Inpsyde GmbH
Link: [Authenticator](https://wordpress.org/plugins/authenticator/)

The core authentication code, and the feed HTTP authenticator, have been adapted with only a few
changes. The special settings page, private feed keys and cookie lifetime setting have been removed.

### Simple Life
Author: Nilambar Sharma
Link: [Simple Life](https://wordpress.org/themes/simple-life/)

The look and feel of the Labbook theme recommended for use with ALP has been inspired by Simple Life,
but the templates are not based on Simple Life's code, but rather based on boilerplate code provided
by [Underscores](https://underscores.me/).
