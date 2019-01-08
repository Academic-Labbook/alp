=== Academic Labbook ===
Contributors: seanleavey
Tags: logbook, coauthor, revisions, references, latex, tex, mathematics, wiki
Requires at least: 5.0.0
Tested up to: 5.0.2
Requires PHP: 7.0
Stable tag: 0.11.0
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
= 0.7.0 =
 - First alpha release.
= 0.7.1 =
 - Fix activation/deactivation post type registration bug.
= 0.7.2 =
 - Fix incorrect function call regression in class-coauthors.php.
 - Prevent direct access to scripts.
 - Prevent directory listing.
= 0.7.3 =
 - Remove option to disable tags.
 - Add settings and tools page links to plugin page.
 - Fix bug with additional edit counts in revisions widget.
 - Theme tweaks.
 - Internal naming changes.
= 0.7.4 =
 - Theme tweaks.
= 0.7.5 =
 - Move media type settings to network admin.
 - Move KaTeX custom script path control to network admin.
 - Setting page tweaks.
 - Change behaviour of [doi] and [arxiv] shortcodes.
 - Fix bug with generated table of contents HTML.
 - Show real revision count on posts (one less than before).
 - Rename settings for consistency.
 - Fix JavaScript bug.
 - Add uninstaller.
 - Add page and tools tests.
= 0.7.6 =
 - Fix bug with coauthor delete on multisite.
 - New automated tests.
= 0.7.7 =
 - Fix bug with coauthor JavaScript on Safari.
 - New automated tests.
= 0.8.0 =
 - Coauthors are now selected using a tag-like taxonomy selector on
   the post edit page, meaning that custom JavaScript is no longer
   required.
 - Removed [arxiv] and [doi] shortcode support in anticipation of a
   Gutenberg block to be added later.
 - New automated tests.
 - Theme tweaks.
= 0.8.1 =
 - Internal work:
   - Properly register edit summaries as post meta.
   - Properly sanitise user-submitted post edit summaries.
   - Rename internal post meta key for edit summaries.
 - Fix post counts showing up as null on admin post list when user
   has no posts.
 - Fix bug with edit summary permission check.
 - Fix bug with coauthor term not being created when network user
   is added to a blog.
 - New automated tests.
= 0.9.0 =
 - Compatibility with Gutenberg:
   - Added edit summary text widget to sidebar.
 - Removed edit summary metabox from classic editor.
 - Classic editor no longer supported.
 - Moved Labbook theme to its own directory to fix stylesheet bug on
   certain types of installation. The Labbook theme must be installed
   separately from now on.
 - Disallow anyone (including admins) from editing or deleting
   coauthor terms, which are essential to the plugin's operation.
 - Update role descriptions.
 - Fix bug where new authors weren't given coauthor terms on sites
   where ALP is not network active.
= 0.10.0 =
 - Remove (unused) translation which is incompatible with 5.0.0.
 - Fixed bug when deleting users from network.
 - Remove ability to hide excerpts on block editor.
= 0.10.1 =
 - Hotfix release.
= 0.10.2 =
 - Hotfix release.
= 0.11.0 =
 - Added TeX block.
 - Revised edit summary editor plugin to use core Gutenberg post meta support
   instead of separate REST endpoint.
 - Self-host KaTeX scripts.
= 1.0.0 =
 - First public release.
