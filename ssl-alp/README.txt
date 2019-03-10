=== Academic Labbook ===
Contributors: seanleavey
Tags: logbook, coauthor, revisions, references, latex, tex, mathematics, wiki
Requires at least: 5.1.0
Tested up to: 5.1.0
Requires PHP: 7.0.0
Stable tag: 0.13.1
License: GNU General Public License v3 or later
License URI: LICENCE

Turn WordPress into a collaborative laboratory notebook supporting multiple
authors on posts, wiki-like pages, cross-references, revision summaries, and
more.

== Description ==
Please also visit [our website](https://alp.attackllama.com/)!

Academic Labbook Plugin (ALP) provides a lightweight but powerful set of tools
on top of core WordPress to allow researchers to write about their work,
leveraging the powerful WordPress platform. With ALP, you can benefit from
WordPress's post compositition, tagging and categorisation, media management and
search tools while enabling features useful in an academic context like
mathematical markup rendering, coauthors, edit summaries and cross-references.

ALP is intended to be used with the corresponding theme [Labbook](https://alp.attackllama.com/documentation/themes/labbook/),
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
with maximum control. Please see [this guide](https://alp.attackllama.com/documentation/installation/)
on the ALP website.

== Changelog ==

= 0.13.1 =
 - Added support for advanced searches, letting users search posts by multiple coauthors, categories
   and tags, and dates.
 - Added setting to control display of advanced search tools to non-logged-in users.

= 0.13.0 =
 - Added support for read flags, letting users keep track of posts they've read or not read and
   allowing them to change this flag per-post and view a list of unread posts (requires Labbook
   theme 1.1.0 or greater for front-end support).
 - Fixed bug whereby coauthors were sent notifications for their own comments.
 - Fixed bug with user widget in non-dropdown mode not showing all authors, and with dropdown mode
   showing authors with zero posts.
 - Removed new default media file feature until Gutenberg bug is fixed.
 - Updated KaTeX to 0.10.1.

= 0.12.3 =
 - Set new default (media file) for image link targets in block editor.

= 0.12.2 =
 - Fixed bug whereby settings were not added to the database for new blogs when the plugin was
   already network active.
 - Added routines to clean up database if ALP is uninstalled.
 - Added extra test.
