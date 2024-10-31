=== RecentComments ===
Contributors: kornelly
Tags: stats, wordpress, posts, statistics, badge, widget, widgets, statistics, sidebar, post, posts, comments, tags, admin, plugin, links, page, trackback, pingback, comment, latest, newest
Requires at least: 2.5
Tested up to: 4.0
Stable tag: 0.2
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

This plugin allows you to show the latest comments, pingbacks or trackbacks in the sidebar of your blog or anywhere else. The plugin comes with widget and gravatar support and gives you the options to finetune the listing the way you like it.

== Description ==

This plugin allows you to show the latest comments, pingbacks or trackbacks in the sidebar of your blog or anywhere else. The plugin comes with widget and gravatar support and gives you the options to finetune the listing the way you like it.

Most themes bring along their own stylesheet rules. This plugin requires very little predefined css rules. You can customize every single listing element by editing the `recentcomments.css`.

== Installation ==

1. Unpack the zipfile latestcomments-X.y.zip
1. Upload folder `recentcomments` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php if(function_exists('recentcomments_display')) recentcomments_display(); ?>` or `<?php if(function_exists('recentcomments_display')) recentcomments_display('comment'); ?>` or `<?php if(function_exists('recentcomments_display')) recentcomments_display('pingback'); ?>` or `<?php if(function_exists('recentcomments_display')) recentcomments_display('trackback'); ?>` in your template or use the sidebar widgets.

== Frequently Asked Questions ==

= Does this plugin displays stuff in the sidebar only? =

No, you can place a function call everywhere in your template. See the installation section.

== Screenshots ==

1. Recent activity, flat listing with gravatars enabled, no hover
1. Recent comments, flat listing with gravatars enabled, no hover
1. Recent pingbacks, listing grouped by post, no hover
1. Recent activity, listing grouped by post, gravatars enabled, no hover
1. Recent comments, listing grouped by post, with gravatars and hover enabled

== Change Log ==

* v0.2 2014-10-13 updated to wordpress 4.0
* v0.1 2009-07-06 initial release
