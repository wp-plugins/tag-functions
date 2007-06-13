=== Plugin Name ===
Contributors: bloertscher
Donate link:
Tags: tags, widgets, sidebar
Requires at least: 2.3-alpha
Tested up to: 2.3-alpha
Stable tag: 1.5

This plugin adds a template function and sidebar widget that produces a tag list similar to the category list.

== Description ==

WordPress 2.3alpha now includes native tagging abilities.  However, there are no functions to list all available tags
analagous to `wp_list_categories()`.  This plugin's purpose is to add those functions.  They are essentially a copy of
category templates modified for tags.  This plugin also adds a sidebar widget making it easy to add a tag list to a
widget-enabled theme.

Once this plugin is enabled, the template function `btl_list_categories()` will be available.  The function will take the
same parameters as [`wp_list_categories()`](http://codex.wordpress.org/Template_Tags/wp_list_categories).

This plugin has been updated for use with the new tag hierarchy.

== Installation ==

1. Upload `btl_tag_functions.php` to the `/wp-content/plugins/` directory
1. Activate the plugin through the 'Plugins' menu in WordPress
1. Place `<?php btl_list_functions() ?>` in your templates
1. Alternatively, a Tags widget will be available as a sidebar widget under Presentation -> Widgets.

