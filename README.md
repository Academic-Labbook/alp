# ALP - Academic Labbook Plugin
ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

## What does ALP do?
Currently, not much, because it's in alpha development. Here are the features
implemented so far...

 - Change logs for posts and pages
   - Unhides revisions meta box in editor by default
 - Force users to be logged in to view (but maybe later add ability to choose
   between network/individual site access)
 - LaTeX support
 - DOI and arXiv persistent references, using shortcodes
 - Disable certain post meta fields mostly suited to commercial sites, like
   excerpts, trackbacks, tags, etc.

...and here are features planned for the future:
 - Disable RSS feeds (NOT disabled by forced login), images also can still be
   accessed but this might require htaccess changes
 - Add wiki pages as a new post type (display contents)
   - (https://codex.wordpress.org/Post_Types#Custom_Post_Types)
   - Keep normal pages for e.g. webcams, "about" pages, etc.
   - Show pages that link to this one?
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
 - Widget under posts containing references to other posts that reference this one (store references as custom fields?)
 - Search within PDFs etc.
 - Labbook theme
   - Enable authors, edited posts, recent comments widgets by default
   - Multiple author display
   - Revision history display
   - Remove Bootstrap dependency, use CSS Grid instead (https://developers.google.com/web/updates/2017/01/css-grid)
 - Optionally display institute logo (also on login page)
 - Shortcodes for linking to Git/SVN commits and other archives
 - Offline download, e.g. [Simply Static](https://wordpress.org/plugins/simply-static/)
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

## Requirements
In general, you should use the latest version of WordPress as that's the one
that will be supported. With auto-updates to WordPress Core, this is easy.

Your users should use up-to-date browsers. The theme bundled with ALP (Alpine)
uses CSS Grid, which is only available in recent versions of the most popular
browsers. The [browsers that don't support CSS Grid](https://caniuse.com/#feat=css-grid)
represent only around 5% of global usage as of March 2018. This project is not
concerned about losing sales from users running out of date browsers!

## Design principles
 - **Clean code**: there's an awful lot of terribly written code floating around
   in the WordPress ecosystem. This plugin attempts to use coding standards, and
   to interfere minimally with the default WordPress behaviour. This will
   ideally reduce development burden after WordPress updates, but also
   potentially improves immunity to security vulnerabilities.
 - **Modular**: most/all features can be enabled or disabled, and work
   independently from each other.
