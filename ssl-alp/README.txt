=== Academic Labbook Plugin ===
Contributors: seanleavey
License: GPL3
License URI: https://www.gnu.org/licenses/gpl-3.0.en.html

ALP (Academic Labbook Plugin) is a WordPress plugin that turns the platform into
a collaborative laboratory notebook.

== Description ==
ALP is intended to provide a lightweight but powerful set of tools to allow
researchers to write about their work, leveraging the WordPress platform.

ALP is currently in alpha development. While everyone is invited and encouraged
to test APL, please bear in mind that it is in early development and therefore
features may be removed or broken between updates.

== Changelog ==
= 0.1.0 =
 - Initial version.
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
 - Moved Alpine theme to its own directory to fix stylesheet bug on
   certain types of installation. The Alpine theme must be installed
   separately from now on.
 - Disallow anyone (including admins) from editing or deleting
   coauthor terms, which are essential to the plugin's operation.
 - Update role descriptions.
 - Fix bug where new authors weren't given coauthor terms on sites
   where ALP is not network active.
= 0.10.0 =
 - Remove (unused) translation which is incompatible with 5.0.0.
 - Removed TeX support for now until a TeX block is developed in favour
   of shortcodes.
 - Bug fix when deleting users from network.
 - Remove ability to hide excerpts on block editor.
= 0.10.1 =
 - Hotfix release.
= 0.10.2 =
 - Hotfix release.