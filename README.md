# ALP - Academic Labbook Plugin
ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

## What does ALP do?
Currently, not much, because it's in alpha development. Here are the features
implemented so far...

 - Disable tags (categories offer all that tags do, and more; tags are
   apparently useful for [SEO](https://en.wikipedia.org/wiki/Search_engine_optimization),
   but we don't care)
 - Change logs for posts and pages (not yet publicly visible)
 - Force users to be logged in to view (but maybe later add ability to choose
   between network/individual site access)
 - LaTeX support
 - DOI persistent references

...and here are features planned for the future:
 - Disable RSS feeds (NOT disabled by forced login), images also can still be
   accessed but this might require htaccess changes
 - Setting: enable revisions list appearance in admin edit post page by default
   for new users
 - Remove trackbacks/pingbacks (can disable via setting)
 - Add wiki pages as a new post type (display contents) (https://codex.wordpress.org/Post_Types#Custom_Post_Types)
 - Support wiki shortcodes (highlight red when page doesn't exist)
 - Multiple author support
 - LaTeX TinyMCE widget
 - More MathJax configuration options
 - Author list widget
 - Recently edited posts widget
 - Change names and behaviours of user types:
   - subscriber -> subscriber
   - contributor -> [remove]
   - author -> [remove, or keep for small project students]
   - editor -> researcher
     - can add new categories
   - administrator -> administrator
 - Disable private posts (useless with forced login)
 - Labbook theme (with default widgets enabled, like authors, edited posts,
   recent comments, etc., and support for displaying multiple authors)
 - Add links to previous post revisions under those posts (dropdown box?)
 - Optionally display institute logo (also on login page)
 - Shortcodes for linking to Git/SVN commits and other archives
 - Remove some post formats (e.g. quote), add new ones, change name of "Aside"
   to "Note"
 - Offline download, e.g. [Simply Static](https://wordpress.org/plugins/simply-static/)
 - arXiv shortcode
 - DOI shortcode validation (https://www.crossref.org/blog/dois-and-matching-regular-expressions/)
 - LSC-specific extensions:
   - DCC shortcodes

ALP is a mixture of new code and code forked from other open source, GPL
licenced plugins. The following list of plugins have been partly adapted into
ALP. All have been modified in some way (e.g. admin settings, class and setting
namespaces, etc.), some more so than others:
 - Coauthors Plus
 - [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions)
 - [Revision Notes](https://wordpress.org/plugins/revision-notes/)
 - [MathJax-LaTeX](https://wordpress.org/plugins/mathjax-latex/)

## Future ideas
Some feature ideas not considered critical, but nice to have:
 - Outreach pages: share some posts/pages publicly.

## Design principles
 - **Clean code**: there's an awful lot of terribly written code floating around
   in the WordPress ecosystem. This plugin attempts to use coding standards, and
   to interfere minimally with the default WordPress behaviour. This will
   ideally reduce development burden after WordPress updates, but also
   potentially improves immunity to security vulnerabilities.
 - **Modular**: most/all features can be enabled or disabled, and work
   independently from each other.
