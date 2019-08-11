# ALP - Academic Labbook Plugin
[![Build Status](https://travis-ci.com/SeanDS/alp.svg?branch=master)](https://travis-ci.com/SeanDS/alp)

ALP leverages the powerful WordPress platform to provide an electronic laboratory notebook fit
for academic and scientific purposes. It's free, open source, regularly updated and attempts to be
minimally invasive on the core WordPress functionality.

Lots of documentation is provided on the [ALP website](https://alp.attackllama.com/).
Before installing, please read and understand the `Development plans in the context of WordPress upgrades`
section below. It explains how new features in WordPress will be tracked by ALP.

ALP is under development, and not feature-complete. While it is being used daily and without much
issue by a large organisation of around 100 scientists, there are surely still some bugs waiting
to be discovered. If you choose to use ALP, [please report any unexpected behaviour](https://alp.attackllama.com/bugs/).
The developer is also willing to help with any installation or setup queries you might have - please
[get in touch](https://alp.attackllama.com/contact/).

## What does ALP do?
ALP makes many extensive additions and modifications to WordPress. Most of the features below are
modular, allowing them to be switched on and off via settings.

### Multiple author support
ALP allows multiple authors to be assigned to single posts. When writing a post, the user can
specify their coauthors using the sidebar. These extra names are then shown in order on the post
page, and the posts appear on each coauthor's archive page and are included in their post counts.

### Edit summaries and revision lists
When editing a published post or page, a user is able to specify an edit summary detailing the
changes they have made. This edit summary appears under the post or page when using the *Labbook*
theme, with a link to the WordPress admin screen showing the changeset (like the "diff" on
Wikipedia).

A new admin screen is also provided, allowing users to navigate the complete history of changes to
posts and pages.

### Cross-references
When a post or page links to another post or page on the same site, the corresponding post or page
is shown in a list under the other post or page. This creates a web of links between posts and
pages. This is particularly useful for finding out whether newer posts on a particular topic have
been made, as long as the author of the newer post remembered to link back to the older one. This
feature acts like a bit like a "related posts" list on other websites.

### Unread flags
ALP tracks whether logged-in users have read new posts. When a new post is made, everyone except
the post's author sees the new post as unread, designated in the *Labbook* theme using a closed
envelope icon next to the post title. When the user reads the post (by clicking the title to visit
the post's single page), the post is marked as read (designated in *Labbook* with an open envelope).
In *Labbook*, users can manually mark a post as read or unread by clicking the envelope. Users can
also click a link in the top bar to view a list of all unread posts.

When a post undergoes significant edits, a post is again marked as unread to all users except the
author.

### Inventory system
ALP adds an inventory system to allow you to create pages for inventory items. These pages can be
used as a central place to store e.g. manuals, schematics, images, etc., and the system lets your
users tag posts with inventory items which then provides links to these pages under the
corresponding posts.

### Advanced search
When used with the *Labbook* theme, ALP adds advanced search capabilities allowing users to
search by categories, tags, coauthors, dates, and keywords. Users can also disallow posts made by
certain users or with certain categories or tags, and search for posts with only certain subsets
of users, categories or tags.

### Privacy options
ALP lets you force users to be logged in to view the site content. Public feeds are only then
available using HTTP authentication and application passwords (see below). The REST API provided
by WordPress is still accessible to logged-in users but only if they configure an application
password see below).

Advanced search may be switched off for non-logged-in users to avoid computationally expensive
searches.

Note: images are still accessible when the direct link is known - this is a shortcoming in WordPress
itself. This can in most cases be mitigated with appropriate HTTP server configuration.

### Mathematical markup support
ALP adds a TeX block to the Gutenberg editor in WordPress 5.0, allowing users to create equations.
The equation is rendered within the editor so the user can check their markup, and the markup can be
edited later alongside the rest of the post.

### Term management
ALP adds the ability to merge tags and categories in bulk.

### Post changes
ALP prevents posts being published with the "Uncategorized" category if the author has specified
another category. In such cases, the "Uncategorized" category is silently removed.

### Page changes
ALP modifies pages to work more like wiki pages. When using the *Labbook* theme, authors and dates
are removed from the title area, a breadcrumb trail is shown back to the home page, and there is
an option to generate and show tables of contents for each page.

### WordPress core changes
The XML-RPC is disabled by ALP, since this is an old scheme which is entirely superceded in
functionality by the REST API.

Post trackbacks are disabled, since these are usually only useful for commercial sites.

When used on a network, extra media (MIME) types can be specified in addition to the core WordPress
ones, to allow your users to upload special files without triggering securty errors.

Some WordPress branding is removed from the admin dashboard pages.

### Role changes
A tool is provided to allow the administrators to change user roles en-masse, to make them more
suitable for academic contexts:
  - *Administrator* is unchanged
  - *Editor* is renamed *Researcher*
  - *Author* is renamed *Intern*
  - *Contributor* is removed
  - *Subscriber* is unchanged
  - *Excluded* is added (for keeping ex-users' posts, comments, etc. on record but not giving them
    access)

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
 - Allows posts to be set as read or unread
 - Supports display of an institute logo and icon
 - Provides two menu locations for providing links to site or external pages or URLs
 - Provides customisable copyright notice and ability to hide branding
 - Responsive to screen size: viewable on mobile, tablet and desktop browsers

## Uninstallation
ALP can be uninstalled via the normal procedure, using the plugin page in the admin area.
Upon uninstallation, all of these changes made by ALP are reversed and all additions to the database
are removed, with the only exception being the user groups. If you have converted user groups using
the included tool, then these will remain as they are. You can manually change user groups using
other plugins. One day ALP may provide a tool to reverse user group changes, but this is not a
high priority.

## Requirements

### WordPress
WordPress 5.1.0 or newer is required. ALP adds a block to and extends the sidebar of the new editor
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
   WordPress behaviour where possible.
 - **Modular**: most features can be enabled or disabled via the settings page, and work
   independently from each other. The plugin can also be used without the corresponding theme,
   though this hides a lot of the useful parts of the plugin from users.

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
[14](https://github.com/WordPress/gutenberg/issues/8032),
[15](https://github.com/WordPress/wordpress-importer/issues/40),
[16](https://github.com/WordPress/gutenberg/issues/10834),
[17](https://github.com/adamsilverstein/mathml-block/issues/12),
[18](https://github.com/WordPress/gutenberg/issues/13749),
[19](https://core.trac.wordpress.org/ticket/46459)

### Co-Authors Plus
Authors: Mohammad Jangda, Daniel Bachhuber, Automattic, Shepherd Interactive, Mark Jaquith

Link: [Co-Authors Plus](https://wordpress.org/plugins/co-authors-plus/)

Some of the code from this plugin has been adapted into ALP, but the main behaviour has been
heavily modified to function using the block editor in WordPress 5.

### WP-Post-Meta-Revisions and Revision Notes
Authors: Adam Silverstein, Helen Hou-Sandí

Links: [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions) and [Revision Notes](https://wordpress.org/plugins/revision-notes/)

These plugins inspired parts of the design of ALP's edit summaries feature.

### Authenticator and Application Passwords
Authors: Inpsyde GmbH, George Stephanis

Links: [Authenticator](https://wordpress.org/plugins/authenticator/) and [Application Passwords](https://wordpress.org/plugins/application-passwords/)

The core authentication code of Authenticator has been the basis for ALP's private site feature.
Parts of Application Passwords have been adapted for REST authentication, but expanded to
authenticate feeds and admin AJAX calls, and the admin interface has been completely replaced.

### Term Management Tools
Author: Cristi Burcă

Link: [Term Management Tools](https://wordpress.org/plugins/term-management-tools/)

Most of the code has been copied with a few minor changes, but the "change taxonomy" function has
been removed and the "merge" function user interface has been tweaked.

### Simple Life
Author: Nilambar Sharma

Link: [Simple Life](https://wordpress.org/themes/simple-life/)

The look and feel of the Labbook theme recommended for use with ALP has been inspired by Simple Life,
but the templates are not based on Simple Life's code, but rather based on boilerplate code provided
by [Underscores](https://underscores.me/).
