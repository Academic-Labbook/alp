=== Academic Labbook ===
Contributors: seanleavey
Tags: logbook, coauthor, revisions, references, latex, tex, mathematics, wiki
Requires at least: 5.6.0
Tested up to: 5.6.0
Requires PHP: 7.1.0
Stable tag: 0.22.0
License: GNU General Public License v3 or later
License URI: LICENCE

Turn WordPress into a collaborative laboratory notebook supporting multiple
authors on posts, wiki-like pages, cross-references, revision summaries, and
more.

== Description ==
Please also visit [our website](https://alp.attackllama.com/)!

Academic Labbook Plugin (ALP) provides a powerful set of tools
on top of core WordPress to allow researchers to write about their work,
leveraging the powerful WordPress platform. With ALP, you can benefit from
WordPress's post compositition, tagging and categorisation, media management and
search tools while enabling features useful in an academic context like
mathematical markup rendering, coauthors, edit summaries and cross-references.

ALP is intended to be used with the corresponding theme [Labbook](https://alp.attackllama.com/documentation/for-administrators/themes/labbook/),
which was created in parallel to ALP, and which enables various public-facing
features provided by the plugin. It is highly encouraged to use this theme, or
derive a child theme from this base. Various features such as coauthors, edit
summaries, cross-references and page contents will not appear visible on the
site without this theme.

ALP is written for WordPress 5.0 and the new block editor it provides. A
textbox is added to post and page edit screens to allow users to leave a brief
summary of the changes they make. These changes are recorded in the database
and displayed alongside posts and pages when used with the "Labbook" theme.
Additionally, a TeX block is added to the editor to allow users to write and
preview mathematical formulae as part of their posts and pages.

ALP provides the ability to set multiple coauthors for posts. This allows
posts for which each user is a coauthor to show up in their post archives and
contribute to their post counts. Any user may edit posts for which they are a
coauthor.

ALP adds the ability to make blogs private, forcing users to log in with their
account in order to access the content. Combined with WordPress's disable login
setting, and perhaps an LDAP authentication plugin, access can be tightly
controlled.

An "Inventory" system is added which lets your users create pages for lab
equipment such as electronics. These pages can be used in whatever way you wish,
such as to store manuals, images, guides and background information. Posts can
also be tagged with inventory, and links to the corresponding inventory pages
are shown under the post.

ALP provides a tool to convert user roles from the blog-centric WordPress
defaults to one more suitable for an academic group. The primary role becomes
"Researcher" instead of "Editor", allowing users with this role to create, edit
and delete all types of content. The "Intern" role is akin to the standard
WordPress "Author", intended for temporary group members, allowing them to make
new posts and edit any post on which they are an author, but not other content.
Finally, a new role, "Excluded", is added, into which any previous group members
can be added in order to disable their access to the site but preserve their
contributions.

Note: ALP is currently in beta. Whilst many bugs have been squashed and the code
is considered to be relatively stable, there may be some issues waiting to be
discovered in the wild. In particular, if you intend to run a private site using
ALP's forced login feature, please accept the risk that this data could be
exposed to the public or to other users on your network if the code does not
work as intended. Consider other means of security beyond this plugin (such as
HTTP digest authentication or institutional intranets) if this risk is
unacceptable.

== Installation ==
Installation is slightly more complicated than a usual WordPress plugin, due to
various configuration steps which are intentionally not automated to provide you
with maximum control. Please see [this guide](https://alp.attackllama.com/documentation/for-administrators/installation/)
on the ALP website.

== Changelog ==

= 0.22.0 =
 - WordPress 5.6 and PHP 7.1 now required.
 - Removed application passwords feature provided by ALP in favour of new core
   application passwords feature. Users using application passwords will now
   have to regenerate them going to their own user page in the admin area. Note
   that this removal was planned as listed in the "Development plans in the
   context of WordPress upgrades" section of the project README file.
 - Set default image and gallery image links in the block editor to the
   corresponding files now that this is [finally supported](https://github.com/WordPress/gutenberg/pull/25578).
   Note that this feature only becomes visible with WordPress 5.6.
 - Change use of wp_localize_script to wp_add_inline_script.
 - "Unread Posts" admin bar menu item is now only shown on the front end.
 - PHP4 style int typecasts replaced with PHP5+ equivalents.
 - Changed users widget to use URLs conforming to the site's current permalink
   setting.
 - Block editor social blocks re-blacklisted after upstream changes enabled
   them.

= 0.21.0 =
 - WordPress 5.5 now required.
 - Renamed deprecated function and hook calls to reflect [changes in core](https://make.wordpress.org/core/2020/07/23/codebase-language-improvements-in-5-5/).
 - Inventory can now be hierarchical and use the post children block.
 - Remove ability in REST API to view other users' post read flags (this was
   never used by the front end).

= 0.20.2 =
 - Have rebuild coauthors tool remove coauthor terms for users who no longer
   exist on the site.
 - Update some URLs pointing to https://alp.attackllama.com/.

= 0.20.1 =
 - Add extra check when adding Unread Posts button to admin bar.
 - Use better hook for adding Unread Posts to admin bar.
 - Various bug fixes:
   - Null check for terms in update_coauthor_term; occasionally we've seen the
     term doesn't exist
   - Null check for when an old revision has been deleted
   - Check that ALP is active on blogs when adding coauthor terms on login when
     ALP is installed in a network but not network active
   - Stop CSS revisions appearing in the Recent Revisions widget.

= 0.20.0 =
 - Fixed bug with terms on login. When logging in, users of blogs would have
   their coauthor term added to the primary blog (usually site ID 1) because of
   the hook 'check_coauthor_term_on_login' which creates the coauthor term if it
   doesn't exist. This meant that users of secondary blogs who were not users of
   the primary blog would still be available for selection as authors on the new
   post screen of the primary blog. They would not however save correctly
   because the users were not members of that blog; they would be silently
   discard. This change fixes the creation of the coauthor terms to only create
   terms on the blogs the user is a member of.

   Existing sites running ALP may still have these terms, however. The rebuild
   coauthor tool will later be updated to delete terms of users who are not
   members of the blog.
 - Fixed potential bug by not now checking post authors are consistent on newly
   created auto-drafts.
 - Added ability to hide revisions on post page and header. This requires the
   latest release of the Labbook theme (1.2.0).

= 0.19.1 =
 - Fixed an issue with creation of coauthor terms during a WordPress import.

= 0.19.0 =
 - Removed the manual WP_Query SQL modifications for including coauthors in
   search results, and replaced with a parameter based search. Due to this
   functionality not being identical, other changes were made to restore
   original functionality. This is quite a major change and needs field testing.
 - Added ability to disable the display of cross-references on supported posts.
 - Updated edit summary JavaScript (just refactoring, no functionality changes).
 - Fixed bug where a link to network settings from the site settings page would
   be displayed to network admins where ALP is active on the site only, not
   network-wide.
 - Updated automatic test configuration.

= 0.18.0 =
 - Fix bug with child block which appeared from 5.3.
 - Fix bug whereby media types detected by WordPress/PHP to have different media
   types to those specified by the network administrator were disallowed. A new
   setting has been added, 'ssl_alp_override_media_types', to control whether
   this mode is enabled.
 - Fix bug with hanging coauthor rebuild.
 - Added permissions checks before displaying and allowing tools to be run (in
   addition to the usual check for 'manage_options' before displaying the tools
   page). This prevents non-super admins on network installations from running
   coauthor and cross-reference rebuild tools.
 - Gracefully handle situations with admin revision tables when revision changes
   cannot be determined.
 - Added a note to the readme about application passwords potentially becoming
   available in core and therefore being liable for future removal.
 - Updated tools page notices.
 - Other minor tweaks.

= 0.17.2 =
 - Fixed bug when rebuilding coauthors on sites with existing posts before ALP
   was installed.

= 0.17.1 =
 - Fixed bug with children block showing all post types and not just those
   sharing the current post's post type.

= 0.17.0 =
 - Added page children block.
 - Added setting to hide social embed blocks from editor.
 - Fixed revision date timezone issue.
 - Fixed incorrectly named TeX block properties.
 - Fixed bug when previewing an inventory item.
 - Split Labbook theme into a separate repository.

= 0.16.0 =
 - Renamed taxonomy term names. This is a BREAKING change and requires some
   custom queries to be run to update old terms. See the GitHub release notes
   for details.
 - Added inventory system.
 - Changed post revisions widget to show only posts with line changes.
 - Changed post revision widget to use transients instead of cache; default
   update time set to 5 minutes.
 - Added check to remove "Uncategorised" category from posts with at least one
   other category.
 - Added check to avoid referenced posts which cannot be read by user from
   showing in the cross-references list.
 - Simplified permission checks when editing custom taxonomy terms.
 - Updated KaTeX to 0.10.2.
 - Fixed bug with coauthors not being assigned to posts on sites with existing
   posts.
 - Fixed bug with unread posts list pagination.
 - Added tool to detect pretty permalink status.
 - Expanded admin documentation.
 - Numerous minor bug fixes.

= 0.15.2 =
 - Fixed bug with media type definitions where trailing spaces caused types not
   to be saved.
 - Disabled header prefetch links to avoid posts being fetched preemptively by
   browsers and therefore being set as read before being viewed.

= 0.15.1 =
 - Removed incorrectly appearing merge action from user application list table.
 - Added link to published post under each revision in admin list table.

= 0.15.0 =
 - Added application passwords feature, removed special feed key support
   (application passwords can be used for this).
 - Added term management tools for merging and reordering the hierarchy of terms
   such as tags and categories.
 - Prevented revisions made to posts before publication from showing in sidebar
   and admin list table.
 - Moved edit summary helper functions out of Labbook theme and into this plugin.

= 0.14.0 =
 - Added revisions lists for posts and pages to admin area.
 - Prevented non-posts being marked as read/unread via the theme.
 - Fixed date issue in recent revisions widget.
 - Added plugin version on tools page.

= 0.13.3 =
 - Fixed bug with advanced search not returning results.
 - Stopped unpublished posts being crossreferenced.
 - Prevented autosaves showing up as post revisions publicly.

= 0.13.2 =
 - Fixed bug with read flags not being settable on single pages.
 - Fixed bug with display names on unread post pages.
 - Removed invalid coauthors attached to posts on save.

= 0.13.1 =
 - Added support for advanced searches, letting users search posts by multiple
   coauthors, categories and tags, and dates.
 - Added setting to control display of advanced search tools to non-logged-in
   users.

= 0.13.0 =
 - Added support for read flags, letting users keep track of posts they've read
   or not read and allowing them to change this flag per-post and view a list of
   unread posts (requires Labbook theme 1.1.0 or greater for front-end support).
 - Fixed bug whereby coauthors were sent notifications for their own comments.
 - Fixed bug with user widget in non-dropdown mode not showing all authors, and
   with dropdown mode showing authors with zero posts.
 - Removed new default media file feature until Gutenberg bug is fixed.
 - Updated KaTeX to 0.10.1.

= 0.12.3 =
 - Set new default (media file) for image link targets in block editor.

= 0.12.2 =
 - Fixed bug whereby settings were not added to the database for new blogs when
   the plugin was already network active.
 - Added routines to clean up database if ALP is uninstalled.
 - Added extra test.
