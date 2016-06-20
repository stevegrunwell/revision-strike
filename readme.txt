=== Revision Strike ===
Contributors: stevegrunwell
Tags: revisions, cron, performance, maintenance
Requires at least: 4.2
Tested up to: 4.3
Stable tag: 0.3.0
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Periodically purge old post revisions via WP Cron.


== Description ==

Unless post revisions are explicitly limited, WordPress will build up a hefty sum of revisions over time. While it's great to have revision history for some recent content, the chances that old revisions will be necessary diminish the longer a post has been published. Revision Strike is designed to automatically remove these unneeded revisions on older, published posts.

= How does it work? =

First, a threshold is set, with a default of 30 days. Once a day, Revision Strike will run and find any post revisions in the database attached to published posts with a post date of at least 30 (or your custom threshold) days ago, and "strike" (tear-down and remove) them from the WordPress database.

= Contributing =

If you'd like to help build Revision Strike, please [visit the plugin's GitHub page](https://github.com/stevegrunwell/revision-strike). Contributors are welcome, and [details can be found in the repo's README file](https://github.com/stevegrunwell/revision-strike#contributing).


== Installation ==

1. Upload the revision-strike/ directory to your WordPress installation's plugin directory (by default, /wp-content/plugins).
2. Activate the plugin through the 'Plugins' menu in WordPress.

Activating the plugin will automatically register a daily WP Cron event to clean up revisions on established posts. If you'd prefer not to wait, you can explicitly purge post revisions through the 'Tools > Revision Strike' page within WordPress or with [WP-CLI](http://wp-cli.org/).


== Frequently Asked Questions ==

= Can I configure how long a post needs to be published before its revisions can be removed? =

Yes. On the Settings > Writing page there is an option to set the default number of days a post must be published before removing its revisions, but out of the box it's 30 days.

= Can I manually run Revision Strike without having to wait for the daily WP Cron event? =

Yup, just visit Tools > Revision Strike within the WordPress admin area. If you're a [WP-CLI](http://wp-cli.org/) user, you can also run Revision Strike from the command line (run `wp revision-strike clean --help` for a full list of options).


== Screenshots ==

1. The Tools > Revision Strike page in action.

== Changelog ==

= 0.2 =

*August 16, 2015*

* Added a "Limit" setting to Settings &rsaquo; Writing. ([#13](https://github.com/stevegrunwell/revision-strike/issues/13))
* Added a "clean-all" WP-CLI command. ([#14](https://github.com/stevegrunwell/revision-strike/issues/14))
* Clarified language on the Settings &rsaquo; Writing and Tools &rsaquo; Revision Strike pages. Props to @GhostToast for the suggestion! ([#16](https://github.com/stevegrunwell/revision-strike/issues/16))
* Strike requests are now batched into groupings of 50 IDs at a time to avoid overwhelming underpowered machines. ([#17](https://github.com/stevegrunwell/revision-strike/issues/17))

= 0.1 =

*August 9, 2015*

* Initial public release

== Upgrade Notice ==

= 0.2 =
Performance enhancements, added a `strike-all` WP-CLI command to automatically clean up *all* of your eligible post revisions in one fell swoop.
