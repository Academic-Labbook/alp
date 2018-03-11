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
 - Labbook theme
   - Revision history display
   - Reference widget under posts:
     - Links to posts referenced by the post
     - Links to DOI and arXiv references

...and here are features planned for the future:
 - Labbook theme
   - Enable authors, edited posts, recent comments widgets by default
   - Multiple author display
   - Top bar for linking to other network sites
 - Disable public access
   - Private feed keys (default feeds NOT disabled by forced login; provide
     unique feeds to each user instead; replace feed URL)
   - Stop images being loaded from outside (might require htaccess changes?)
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
 - Reference widget under posts:
   - Links to posts that reference this one
 - Advanced search:
   - Search within PDFs etc.
   - Advanced search page with options to search by revision, etc.
 - Optionally display institute logo (also on login page)
 - Shortcodes for linking to Git/SVN commits and other archives
 - Offline download, e.g. [Simply Static](https://wordpress.org/plugins/simply-static/)
 - DOI shortcode validation (https://www.crossref.org/blog/dois-and-matching-regular-expressions/)
 - LSC-specific extensions:
   - DCC shortcodes
     - Add entries to reference widget

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
   in the WordPress ecosystem. This plugin attempts to conform to coding
   standards, and to interfere minimally with the default WordPress behaviour
   where possible.
 - **Modular**: most/all features can be enabled or disabled, and work
   independently from each other.

## Credits
ALP is a mixture of new code and code forked from other open source, GPL
licenced plugins and themes.

The following list of plugins have been partialy adapted into ALP. All have been
modified in some way (e.g. admin settings, class and setting namespaces, etc.),
some more so than others:
 - Coauthors Plus
 - [WP-Post-Meta-Revisions](https://github.com/adamsilverstein/wp-post-meta-revisions)
 - [Revision Notes](https://wordpress.org/plugins/revision-notes/)
 - [MathJax-LaTeX](https://wordpress.org/plugins/mathjax-latex/)
 - [Simple Life](https://wordpress.org/themes/simple-life/)

The code specific to this plugin was authored by [Sean Leavey](https://attackllama.com/).
