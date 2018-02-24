# ALP - Academic Labbook Plugin
ALP leverages the powerful WordPress platform to provide an academic
labbook/logbook fit for purpose. It's free, open source, and attempts to be
minimally invasive on the core WordPress functionality, which should allow you
to install additional plugins as you like.

## What does ALP do?
Currently, not much, because it's in alpha development. Here are the features
implemented so far...

 - Disable tags for posts (categories offer all that tags do, and more; tags
   are apparently useful for [SEO](https://en.wikipedia.org/wiki/Search_engine_optimization)
   which we don't care about)

...and here are features planned for the future:
 - Force category selection for posts (avoiding the default "Uncategorised")
 - Multiple author support
 - LaTeX support
 - Author widget
 - Recently edited posts widget
 - Labbook theme (with default widgets enabled, like authors, edited posts,
   recent comments, etc., and support for displaying multiple authors)
 - Add links to previous post revisions under those posts (dropdown box?)
 - Optionally display institute logo
 - Wiki
 - Shortcodes for linking to Git/SVN commits and other archives
 - Remove some post formats (e.g. quote), add new ones, change name of "Aside"
   to "Note"
 - Settings page with core WordPress settings related to labbook (e.g. public
   visibility, display posts as full/summary)

ALP is a mixture of new code and code forked from other open source, GPLv3
licenced plugins. The following list of plugins have been adopted in ALP. All
have been modified to fit into ALP (e.g. admin settings, class and setting
namespaces, etc.):
 - Coauthors Plus

## Goals
 - **Clean code**: there's an awful lot of terribly written code floating around
   in the WordPress ecosystem. This plugin attempts to use coding standards, and
   to interfere minimally with the default WordPress behaviour. This will
   ideally reduce development burden after WordPress updates, but also
   potentially improves immunity to security vulnerabilities.
 - **Modular**: most/all features can be enabled or disabled, and work
   independently from each other.
